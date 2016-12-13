<?php


namespace App\Http\Controllers;

use App\Exceptions\ExceptionWithCustomCode;
use App\Exceptions\Search\UserCannotLaunchSearchException;
use App\Exceptions\ValidationException;
use App\Helpers\ErrorCode;
use App\Helpers\Notifier;
use App\Models\Hug;
use App\Models\Search;
use App\Models\SearchList;
use App\Models\SearchListUser;
use App\Models\User;
use App\Models\UserSearchTimeout;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use App\Helpers\Loggers\SearchLogger;

class SearchController extends Controller
{

    const FINISH_STATUS_SUCCESSFUL     = 1;
    const FINISH_STATUS_TIMEOUT        = 2;
    const FINISH_STATUS_NOT_SUCCESSFUL = 3;
    const FINISH_STATUS_STOPPED        = 4;


    // Errori


    /**
     * L'utente inizia la ricerca.
     * Input:
     *      - Cordinate dell'utente nel momento della richiesta
     * Output:
     *      - Dettagli di ricerca
     *
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function begin(Request $request)
    {

            $this->validate($request, [
                'geo_latitude'  => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
                'geo_longitude' => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
            ]);


        $data = $request->only([
                                   'geo_latitude',
                                   'geo_longitude',
                               ]);

        $user = $this->getAuthenticatedUser();

        if ($user === null) {
            // Utente non autorizzato
            throw new ExceptionWithCustomCode("Utente non autorizzato", ErrorCode::USER_NOT_AUTHORISED, 403);
        }

		SearchLogger::debug("Controllo che l'utente possa lanciare una ricerca... [{$user->id}]");
        $this->checkIfUserCanLaunchSearch($user);

        // Creo istanza di ricerca
	    SearchLogger::debug("Creo istanza di ricerca...");
        $search                = new Search;
        $search->user_id       = $user->id;
        $search->max_duration  = 600; // 10 minuti //$user->max_duration;
        $search->max_distance  = 10000; // 10.000 KM //$user->max_distance;
        $search->geo_latitude  = $data['geo_latitude'];
        $search->geo_longitude = $data['geo_longitude'];
        $search->ip            = $request->ip();
        $search->save();

	    SearchLogger::debug("Istanza creata. ID {$search->id}");
	    SearchLogger::setSearchId($search->id);

        // Aggiorno posizione dell'utente
        $user->geo_last_update = new Carbon;
        $user->geo_latitude    = $data['geo_latitude'];
        $user->geo_longitude   = $data['geo_longitude'];
	    SearchLogger::debug(sprintf("Aggiornata posizione dell'utente. Lat: %s, Lng: %s", $data['geo_latitude'], $data['geo_longitude']));


        // Creo una nuova lista di ricerca
	    SearchLogger::debug("Creo la lista di ricerca...");
        $search->createNewList();
	    SearchLogger::debug("Lista creata.");

        // Si inizia!
	    SearchLogger::debug("Iniziamo il processo di ricerca.");
        $inProgress = $this->process($search);

        if ($inProgress === true) {
            // Aggiorno status dell'utente
            $user->status = User::STATUS_SEARCHING;
        }

        // Salvo l'utente
        $user->save();

        return parent::response([
                                    'search'        => $search,
                                    'inProgress'    => $inProgress,
                                    'nextKeepAlive' => Search::KEEP_ALIVE_INTERVAL,
                                ]);
    }


    /**
     * L'utente A ferma esplicitamente la ricerca.
     * INPUT:
     * - id: ID della ricerca
     * OUTPUT:
     * - Parametri di ricerca
     *
     * Restituisce errori nei seguenti casi
     * - La ricerca non esiste
     * - La ricerca non è stata creata dall'utente
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function stop(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|numeric|exists:searches,id',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            return parent::response([], $errors);
        }

        /**
         * @var Search $search
         */
        $search = Search::whereId($request->input('id'))->first();
        SearchLogger::setSearchId($search->id);

        $this->integrityChecks($search);

        if ($search->isFinished() !== true) {
	        SearchLogger::debug("Richiesta chiusura.");
            $this->finish($search, static::FINISH_STATUS_STOPPED);

            $user = $this->getAuthenticatedUser();
            // Ripristino la status disponibile
	        SearchLogger::debug("Ripristino lo status dell'utente.");
            if ($user->status == User::STATUS_SEARCHING) {
                $user->status = User::STATUS_AVAILABLE;
                $user->save();
            }
        }


        return parent::response([
                                    'search' => $search,
                                ]);
    }


    /**
     * L'utente A esprime il suo interesse per continuare la ricerca
     * INPUT:
     *  - id: ID della ricerca
     * OUTPUT:
     * - Parametri di ricerca
     * - Se la ricerca è in progress o meno
     * - Il numero di secondi che l'app di A deve aspettare prima di inviare nuovamente il segnale
     *
     * Se la ricerca è non in progress basta consultare i parametri relativi al success e timeout
     * - Se success = 1, allora l'app di A non dovrà inviare alcun segnale e sarà il server a breve
     *      a comunicare ad A l'esito positivo
     * - Se success = 0:
     *      - se timeout = 1, Non è stato trovato alcun utente entro il tempo stabilito
     *      - se timeout = 0, Non è stato trovato alcun utente
     *
     * Restituisce errori nei seguenti casi
     * - La ricerca non esiste
     * - La ricerca non è stata creata dall'utente A
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function proceed(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|numeric|exists:searches,id',
                'geo_latitude'  => 'sometimes|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
                'geo_longitude' => 'sometimes|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            return parent::response([], $errors);
        }

        $user = $this->getAuthenticatedUser();
        $user->updatePosition($request->input('geo_latitude'), $request->input('geo_longitude'));

        /**
         * @var Search $search
         */
        $search = Search::whereId($request->input('id'))->first();
        SearchLogger::setSearchId($search->id);
	    SearchLogger::debug("Sollecito di ricerca.");

        // Controlli di integrità
        $this->integrityChecks($search);

        $nextKeepAlive = 0;
        if ($search->isFinished() !== true) {
            $lastFetchAt     = Carbon::createFromFormat('Y-m-d H:i:s', $search->getLastFetchDate());
            $lastFetchAtDiff = Carbon::now()->diffInSeconds($lastFetchAt);
            if ($lastFetchAtDiff > Search::KEEP_ALIVE_INTERVAL + Search::KEEP_ALIVE_TOLERANCE) {

	            SearchLogger::debug("Sollecito valido.");
                // E' un sollecito valido (il server era fermo, l'ultimo utente estratto non ha risposto), registro il timeout [TIMEOUT NON CONNESSO]
                $this->userResponseTimeout($search);

                // Continuo la ricerca
	            SearchLogger::debug("Continuo la ricerca.");
                $inProgress = $this->process($search);

                $nextKeepAlive = Search::KEEP_ALIVE_INTERVAL + Search::KEEP_ALIVE_TOLERANCE;
            } else {

	            SearchLogger::debug("Il server sta già continuando. Comunico solo tra quanto risollecitare");
                // Il server sta proseguendo da solo, dico ad A tra quanti secondi deve risollecitare
                $inProgress    = true;
                $nextKeepAlive = Search::KEEP_ALIVE_INTERVAL - Carbon::now()->diffInSeconds($lastFetchAt) + Search::KEEP_ALIVE_TOLERANCE;
            }

            // Aggiorno il keep_alive
            $search->keep_alive = Carbon::now()->toDateTimeString();
            $search->save();

	        SearchLogger::debug("Next keep alive: $nextKeepAlive");

        } else {
            // E' già conclusa, è compito del server contattarmi. La risposta dovrebbe essere ricevuta a breve.
	        SearchLogger::debug("Ricerca già conclusa. Tra poco verrà ricontatta l'app.");
            $inProgress = false;
        }

        if ($inProgress !== true) {
            // Nessun keep_alive è richiesto
            $nextKeepAlive = -1;

            // Se la ricerca si è conclusa senza successo, riporto lo status dell'utente su disponibile
            if (!$search->success) {
            	SearchLogger::debug("Ricerca non è andata a buon fine. Riporto lo status dell'utente su disponibile.");
                if ($user->status == User::STATUS_SEARCHING) {
                    $user->status = User::STATUS_AVAILABLE;
                    $user->save();
                }
            }
        }

        return parent::response([
                                    'search'        => $search,
                                    'inProgress'    => $inProgress,
                                    'nextKeepAlive' => $nextKeepAlive,
                                ]);
    }


	/**
	 * Gestisce le risposte degli utenti in seguito ad una richista di abbraccio.
	 *
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws ExceptionWithCustomCode
	 */
    public function userResponse(Request $request)
    {
        $responseType = last(explode('.', $request->route()->getName()));

        if (!in_array($responseType, ['accept', 'reject', 'noResponse'])) {
            // Risposta invalida
            throw new ExceptionWithCustomCode("Risposta non valida", ErrorCode::INVALID_USER_RESPONSE, 404);
        }

        try {
            $this->validate($request, [
                'id' => 'required|numeric|exists:searches,id',
                'geo_latitude'  => 'sometimes|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
                'geo_longitude' => 'sometimes|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/'
            ]);
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            return parent::response([], $errors);
        }

        $user = $this->getAuthenticatedUser();

        /**
         * @var Search $search
         */
        $search = Search::whereId($request->input('id'))->whereNull('finished_at')->firstOrFail();
	    SearchLogger::debug("Risposta dell'utente {$user->id} per la ricerca. {$search->id}");
	    SearchLogger::setSearchId($search->id);

        if ($user->id != $search->getLastFetchedUserId()) {
            // Non può rispondere a questa ricerca
            // TODO: Forse bisognerebbe prevedere qualcosa, soprattuto nell'accept. (oppure gestirlo nell'app?)
	        $error = "Non puoi rispondere a questa ricerca";
	        SearchLogger::error($error);
            throw new ExceptionWithCustomCode($error, ErrorCode::USER_NOT_AUTHORISED, 403);
        }

	    SearchLogger::debug("Tipo risposta: $responseType");

        switch ($responseType) {
            case 'accept':

                $this->userResponseAccept($search, $user);
                $user->updatePosition($request->input('geo_latitude'), $request->input('geo_longitude'));

                // Termino la ricerca
	            SearchLogger::debug("Termino la ricerca con successo.");
                $this->finish($search, self::FINISH_STATUS_SUCCESSFUL);

                // salvo alcuni dati utili
                $search->setFoundUserId($user->id);
                $search->save();

                // Notifico l'utente che ha avviato la ricerca
	            SearchLogger::debug("Notifico l'utente che ha avviato la ricerca.");
                $this->notifyTheSearcher($search);
                break;

            case 'reject':
                $this->userResponseReject($search, $user);

                // Continuo la ricerca
	            SearchLogger::debug("Continuo la ricerca.");
                $this->process($search);
                break;

            case 'noResponse':
                $this->userResponseNoResponse($search, $user);

                // Continuo la ricerca
	            SearchLogger::debug("Continuo la ricerca.");
                $this->process($search);
                break;

            default:

        }

        return parent::response([]);
    }

    /**
     * Si occupa di processera la lista della ricerca estraendo un nuovo utente disponibile.
     *
     * Se trova un utente si occupa di contattarlo.
     * Termina la ricerca e ritorna falso qualora non venga trovato alcun utente per la lista.
     *
     * @param Search $search
     *
     * @return bool Vero se la ricerca sta proseguendo, falso se è terminata
     */
    protected function process($search)
    {
        // E' una ricerca conclusa?
        if ($search->isFinished()) {
	        SearchLogger::debug("La ricerca è conclusa.");
            return false;
        }

        // Flush dell'ultimo fetch
	    SearchLogger::debug("Flush dell'ultimo utente fetchato.");
        $search->flushLastFetch();

        // Verifico che la ricerca non abbia superato il massimo tempo consentito
	    SearchLogger::debug("Controllo se la ricerca non ha superato il massimo tempo consentito.");
	    $searchingFor = Carbon::now()->diffInSeconds(Carbon::createFromFormat('Y-m-d H:i:s', $search->created_at), true);
        if ($searchingFor >= $search->max_duration) {
	        SearchLogger::debug("Ricerca troppo lunga. La termino");
            // Termino la ricerca
            $this->finish($search, self::FINISH_STATUS_TIMEOUT);

            return false;
        }

        $newListCreated = false;
        // Verifico se la lista è "troppo vecchia"
	    SearchLogger::debug("Controllo se la lista di utenti è troppo vecchia...");
        $list         = $search->getLastList();
        $listDateDiff = Carbon::now()->diffInSeconds(Carbon::createFromFormat('Y-m-d H:i:s', $list->created_at));
        if ($listDateDiff > SearchList::OLD_LIST_AFTER_SECONDS) {
            // Creo una nuova lista
	        SearchLogger::debug("Lista troppo vecchia. Ne creo una nuova");
            $search->createNewList();
            $newListCreated = true;
        }

        // Estraggo un utente
	    SearchLogger::debug("Estraggo un utente.");
        $user = $search->fetchUser();

        if (empty($user)) { // Nessun utente trovato
	        SearchLogger::debug("Nessun utente trovato nella lista.");
            // Se la lista non è stata appena creata, provo a crearne un'altra e a fare un nuovo fetch
            if ($newListCreated === false) {
	            SearchLogger::debug("Creo una nuova lista. E provo ad estratte un nuovo utente.");
                $search->createNewList();
                $user = $search->fetchUser();
            }
        }

        if ($user instanceof User) {
            // Utente trovato, contattiamolo
	        SearchLogger::debug('Utente trovato: ' . $user->toJson());
            $this->contactFetchedUser($search, $user);

            return true;
        } else {
	        SearchLogger::debug("Nessun utente trovato. Termino la ricerca");
            // Non ho trovato nessun utente, termino la ricerca
            $this->finish($search, static::FINISH_STATUS_NOT_SUCCESSFUL);

            return false;
        }
    }

    /**
     * @param Search $search
     * @param int    $status
     */
    protected function finish($search, $status)
    {
	    SearchLogger::debug("Chiusura ricerca. Status $status");
        $search->finished_at = Carbon::now()->toDateTimeString();

        switch ($status) {
            case static::FINISH_STATUS_SUCCESSFUL:
                $search->success = 1;
                break;

            case static::FINISH_STATUS_TIMEOUT:
                $search->timeout = 1;
                break;

            case static::FINISH_STATUS_NOT_SUCCESSFUL:
                break;

            case static::FINISH_STATUS_STOPPED:
                $search->stopped = 1;
                break;

            default:
        }

        // Flush dell'ultimo fetch
        $search->flushLastFetch();

        // Salvo la ricerca
        $search->save();
    }


    /**
     *Invia la notifica all'utente. Il recupero dei dati relativi all'utente che ha inviato la richiesta (feedback, nome ecc..) verrà fatto dall'app
     * dell'utente destinatario.
     *
     * @param Search $search
     * @param User   $user
     */
    private function contactFetchedUser(Search $search, User $user)
    {
	    SearchLogger::debug("Contatto l'utente...");
        Notifier::send($user, 'search', 'fetched', [
            "search_id"    => $search->id, // Id della ricerca per il quale l'utente dovrà rispondere
            "wait_you_for" => Search::MAX_USER_RESPONSE_TIME, // Numero di secondi entro il quale l'utente può rispondere
            "sent_at"      => Carbon::now()->toDateTimeString(), // Data di invio della richiesta, potrebbe essere utile
        ]);
    }


    /**
     * @param $search
     *
     * @throws Exception
     */
    private function integrityChecks($search)
    {
        $user = $this->getAuthenticatedUser();
        if ($search->user_id != $user->id) {
            throw new Exception('La ricerca non appartiene all\' utente', 403);
        }
    }

    /**
     * @param Search $search
     * @param User   $user
     *
     * @return bool
     */
    private function userResponseReject(Search $search, User $user)
    {
        $searchList = $search->getLastList();

        /**
         * @var SearchListUser $searchListUser
         */
        $searchListUser                = SearchListUser::whereUserId($user->id)->whereSearchListId($searchList->id)->first();
        $searchListUser->response_type = SearchListUser::RESPONSE_TYPE_REJECTED;
        $searchListUser->responsed_at  = Carbon::now()->toDateTimeString();
        $searchListUser->save();

        // Se lo status era su pendent, lo riporto su disponibile, faccio il controllo perchè potrebbe accadere che l'utente abbia cambiato nel frattempo il proprio status e in questo caso non deve tornare su disponibile
        if ($user->status == User::STATUS_PENDENT) {
            $user->status = User::STATUS_AVAILABLE;
            $user->save();
        }

        return true;
    }

    /**
     * [TIMEOUT CONNESSO]
     * L'utente non ha fatto selezionato alcuna opzione nel terminale ma il terminale è connesso alla rete ed ha mandato
     * una risposta di timeout al server.
     *
     * @param Search $search
     *
     * @param User   $user
     *
     * @return bool
     */
    private function userResponseNoResponse(Search $search, User $user)
    {
        $searchList = $search->getLastList();

        /**
         * @var SearchListUser $searchListUser
         */
        $searchListUser                = SearchListUser::whereUserId($user->id)->whereSearchListId($searchList->id)->first();
        $searchListUser->response_type = SearchListUser::RESPONSE_TYPE_TIMEOUT;
        $searchListUser->responsed_at  = Carbon::now()->toDateTimeString();
        $searchListUser->save();

        // Registro il timeout
        $this->registerUserTimeout($search, $user);

        return true;
    }

    /**
     * [TIMEOUT NON CONNESSO]
     * Il server non ha avuto alcuna risposta dall'utente entro il tempo limite ed è arrivato un sollecito da parte
     * dell'utente che cerca.
     *
     * @param Search $search
     */
    private function userResponseTimeout(Search $search)
    {
	    SearchLogger::debug("Timeout non connesso.");
        $searchList = $search->getLastList();
        $user       = $search->getLastFetchedUser();

        /**
         * @var SearchListUser $searchListUser
         */
        $searchListUser                = SearchListUser::whereUserId($user->id)->whereSearchListId($searchList->id)->first();
        $searchListUser->response_type = SearchListUser::RESPONSE_TYPE_TIMEOUT;
        $searchListUser->responsed_at  = Carbon::now()->toDateTimeString();
        $searchListUser->save();

        // Registro il timeout
        $this->registerUserTimeout($search, $user);
    }


    /**
     * @param Search $search
     * @param User   $user
     */
    private function registerUserTimeout(Search $search, User $user)
    {
    	SearchLogger::debug("Timeout dell'utente {$user->id}.");

	    $timeout = new UserSearchTimeout;

        $timeout->timed_out_at = Carbon::now()->toDateTimeString();
        $timeout->search_id    = $search->id;
        $timeout->user_id      = $user->id;

        $timeout->save();
    }

    /**
     * @param Search $search
     * @param User   $user
     *
     * @return  bool
     */
    private function userResponseAccept(Search $search, User $user)
    {
        $searchList = $search->getLastList();

        /**
         * @var SearchListUser $searchListUser
         */
        $searchListUser                = SearchListUser::whereUserId($user->id)->whereSearchListId($searchList->id)->first();
        $searchListUser->response_type = SearchListUser::RESPONSE_TYPE_ACCEPTED;
        $searchListUser->responsed_at  = Carbon::now()->toDateTimeString();
        $searchListUser->save();

        return false;
    }

    /**
     * Notifica l'utente che ha avviato la ricerca che la ricerca è stata conclusa con successo. Sarà l'app ad occuparsi
     * di inviarmi la conferma a voler entrare nell'abbraccio.
     *
     * @param Search $search
     */
    private function notifyTheSearcher(Search $search)
    {
        $user = User::whereId($search->user_id)->first();

        Notifier::send($user, 'search', 'userFound', [
            'search_id' => $search->id,
        ]);
    }

    /**
     * Controlla se l'utente può avviare la ricerca.
     *
     * Un utente non può avviare una ricerca quando:
     *  - Ha già una ricerca in corso.
     *  - Ha già un abbraccio in corso
     *  - Non ha lasciato il feedback per un'altro abbraccio concluso.
     *
     * @param User $user
     *
     * @throws UserCannotLaunchSearchException
     */
    private function checkIfUserCanLaunchSearch(User $user)
    {
        $search = Search::whereUserId($user->id)->whereNull('finished_at')->first();
        if ($search instanceof Search) {
	        $warn = "Un'altra ricerca è in corso - Search id: {$search->id}";
	        SearchLogger::warning($warn);
        	throw new UserCannotLaunchSearchException($warn, ErrorCode::PREVIOUS_SEARCH_IN_PROGRESS);
        }

        $hug = Hug::whereNull('closed_at')
            ->where(function ($query) use ($user) {
                /**
                 * @var Builder $query
                 */
                $query
                    ->where('user_seeker_id', '=', $user->id)
                    ->orWhere('user_sought_id', '=', $user->id);
            })
            ->first();

        if ($hug instanceof Hug) {
	        $warn = "E' coinvolto in un'altro abbraccio - Hug id: {$hug->id}";
	        SearchLogger::warning($warn);
            throw new UserCannotLaunchSearchException($warn);
        }

        $hugWithOutFeedBack = DB::table('hugs AS HUG')
            ->leftJoin('user_hug_feedbacks AS FEEDBACK_AS_SEEKER', function ($join) {
                /**
                 * @var JoinClause $join
                 */
                $join
                    ->on('HUG.user_seeker_id', '=', 'FEEDBACK_AS_SEEKER.user_id')
                    ->on('HUG.id', '=', 'FEEDBACK_AS_SEEKER.hug_id')
                ;
            })
            ->leftJoin('user_hug_feedbacks AS FEEDBACK_AS_SOUGHT', function ($join) {
                /**
                 * @var JoinClause $join
                 */
                $join
                    ->on('HUG.user_sought_id', '=', 'FEEDBACK_AS_SOUGHT.user_id')
                    ->on('HUG.id', '=', 'FEEDBACK_AS_SOUGHT.hug_id')
                ;
            })
            ->whereNotNull('HUG.closed_at')
            ->whereNull('FEEDBACK_AS_SEEKER.id') // Se sono entrambi nulli, vuol dire che non ha lasciato alcun feddback
            ->whereNull('FEEDBACK_AS_SOUGHT.id')
            ->where(function ($query) use ($user) {
                /**
                 * @var Builder $query
                 */
                $query
                    ->where('HUG.user_seeker_id', '=', $user->id)
                    ->orWhere('HUG.user_sought_id', '=', $user->id);
            })
            ->first(['HUG.*'])
        ;

        if (!empty($hugWithOutFeedBack)) {
	        $warn = "Non ha lasciato il feedback per un abbraccio concluso - Hug id: {$hugWithOutFeedBack->id}";
	        SearchLogger::warning($warn);
            throw new UserCannotLaunchSearchException($warn);
        }

    }

}