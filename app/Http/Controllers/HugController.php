<?php


namespace App\Http\Controllers;


use App\Exceptions\ValidationException;
use App\Helpers\Loggers\HugLogger;
use App\Helpers\Notifier;
use App\Models\Hug;
use App\Models\Search;
use App\Models\User;
use App\Models\UserHugFeedback;
use App\Models\UserHugSelfie;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Exceptions\ExceptionWithCustomCode;
use App\Helpers\ErrorCode;

class HugController extends Controller
{

    /**
     * L’abbraccio viene aperto quando A comunica al server di essere ancora disponibile (dopo l’accettazione di B).
     * Questa funzione sarà quindi richiamata solo da A.
     * L'utente B non può creare l'abbraccio. Può solo confermarlo
     * Un abbraccio può essere creato solo in seguito ad una ricerca andata a buon fine. Quindi in input bisogna
     * passare l'id della ricerca con il quale poter effettuare i controlli
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createHug(Request $request)
    {

        $this->validate($request, [
            'search_id' => 'required|numeric|exists:searches,id',
            'geo_latitude' => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
            'geo_longitude' => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/'
        ]);


        $user = $this->getAuthenticatedUser();
        $user->updatePosition($request->input('geo_latitude'), $request->input('geo_longitude'));

        HugLogger::debug("Richiesta creazione abbraccio dall'utente {$user->id} per la ricerca {$request->get('search_id')}.");

        /**
         * @var Search $search
         */
        $search = Search::whereId($request->get('search_id'))
            ->whereUserId($user->id)
            ->whereNotNull('finished_at')
            ->whereSuccess(1)
            ->where('finished_at', '>=', Carbon::now()->subMinutes(1)->toDateTimeString()) // l'abbracccio dovrebbe essere aperto subito dopo la chiusera dell'abbraccio. 1 minuto è già tanto.., in ogni caso probabilmente non ci sarà più B
            ->firstOrFail();

        // Tutto ok, creo abbraccio!
	    HugLogger::debug("Ricerca associata: {$search->toJson()}");
	    $hug                           = new Hug;
        $hug->search_id                = $search->id;
        $hug->user_seeker_id           = $user->id;
        $hug->user_sought_id           = $search->getFoundUserId();
        $hug->user_seeker_last_checkin = Carbon::now()->toDateTimeString();

        $hug->save();
        HugLogger::setHugId($hug->id);
	    HugLogger::debug("Istanza di abbraccio creata: {$hug->toJson()}");

        // Aggiorno lo status dell'utente
	    HugLogger::debug("Aggiorno lo status dell'utente.");
        $user->status = User::STATUS_HUGGING;
        $user->save();

        // Notifico l'altro utente
	    HugLogger::debug("Notifico l'utente trovato.");
        $this->alertSoughtUser($hug);

        // TODO: l'utente è "dentro" l'abbraccio!, l'app dovrebbe portarlo alla view

        $soughtUser = User::whereId($hug->user_sought_id)->first();

        return parent::response([
                                    "hug" => $hug,
                                    "sought_user" => $soughtUser,
                                ]);
    }


	/**
	 * Funzione richiamata da B nel caso in cui sta aspettando che arrivi la conferma che l'abbraccio è stato creato ma
	 * non riceve nulla entro un tot di secondi. Utilizza quindi questa funzione per richiedere se l'abbraccio è stato
	 * creato e se può entrare
	 *
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws ExceptionWithCustomCode
	 */
    public function checkForHug(Request $request)
    {

        $this->validate($request, [
            'search_id' => 'required|numeric|exists:searches,id',
        ]);


        $user = $this->getAuthenticatedUser();

	    HugLogger::debug(sprintf("Richiesta status abbraccio della ricerca %d da parte dell'utente %d", $request->get('search_id'), $user->id));

        /**
         * @var Hug    $hug
         * @var Search $search
         */
        $hug    = Hug::whereSearchId($request->get('search_id'))->whereNull('closed_at')->first();
        $search = Search::whereId($request->get('search_id'))->first();

        if (empty($hug)) {
        	HugLogger::debug("Abbraccio non trovato. Riprova fra un pò.");
            // Riprova o lascia perdere!
            return parent::response([
                                        "hug"     => null,
                                        "retryIn" => 15, // Riprova fra qualche secondo. L'app dovrebbe registrare i tentativi effettuati e fermarsi dopo un tot
                                    ]);
        } else {
            // L'abbraccio esiste.
	        HugLogger::debug("Abbraccio trovato. Hug ID: {$hug->id}");
	        HugLogger::setHugId($hug->id);
            if ($hug->user_sought_id != $user->id) {
            	$warn = "Utente non autorizzato";
	            HugLogger::warning($warn);
                // Non è lui quello che è stato trovato
                throw new ExceptionWithCustomCode($warn, ErrorCode::USER_NOT_AUTHORISED, 403);
            }

            if (!empty($hug->user_sought_last_checkin)) {
                // Ha già fatto l'accesso
	            $warn = "Hai già effettuato l'accesso";
	            HugLogger::warning($warn);
                //abort(400); // Forse dovremmo evitare errore in questo caso e semplicemente dirgli "guarda che già sei dentro"?
                //throw new ExceptionWithCustomCode($warn, ErrorCode::INVALID_REQUEST, 400);
                //LK91: anzichè generare l'errore ritorno l'abbraccio, la posizione ed un alert
                $seekerUser = User::whereId($hug->user_seeker_id)->first();

                return parent::response([
                    "hug" => $hug,
                    "seeker_user_geo_latitude" => $seekerUser->geo_latitude,
                    "seeker_user_geo_longitude" => $seekerUser->geo_latitude,
                    "already_joined"            => true,

                ]);
            }

            // Ok può entrare
	        HugLogger::debug("Entra nell'abbraccio");
            $this->join($hug, $user);
        }

        $seekerUser = User::whereId($hug->user_seeker_id)->first();

        return parent::response([
                                    "hug" => $hug,
                                    "seeker_user_geo_latitude" => $seekerUser->geo_latitude,
                                    "seeker_user_geo_longitude" => $seekerUser->geo_latitude,

                                ]);
    }


    /**
     * Richiesta inviata da parte B per confermare l'accesso all'abbraccio creato da A. Se anche questo è completa
     * entrambi sono dentro l'abbraccio.
     *
     * @param Request $request
     *
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function joinHug(Request $request, $id)
    {

        $this->validate($request, [
            'search_id' => 'required|numeric|exists:searches,id',
        ]);


        $user = $this->getAuthenticatedUser();
        HugLogger::debug(sprintf("Accesso abbraccio della ricerca %d da parte dell'utente %d", $request->get('search_id'), $user->id));

	    /**
         * @var Hug $hug
         */
        $hug = Hug::whereId($id)->whereSearchId($request->get('search_id'))->whereUserSoughtId($user->id)->first();
		HugLogger::setHugId($hug->id);

        HugLogger::debug("Entra nell'abbraccio");
        $this->join($hug, $user);

        $seekerUser = User::whereId($hug->user_seeker_id)->first();

        return parent::response([
                        "seeker_user" => $seekerUser
        ]);
    }


    /**
     * L'utente refresha i dati dell'abbraccio aggiornando contemporaneamente la data del checkIn
     *
     * Sarebbe utile per aggiornare la posizione dell'altro, nuovi selfie ecc..
     *
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request, $id)
    {
        $this->validate($request, [
            'geo_latitude'  => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
            'geo_longitude' => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
        ]);

        $user = $this->getAuthenticatedUser();
        $user->updatePosition($request->input('geo_latitude'), $request->input('geo_longitude'));
	    HugLogger::debug(sprintf("Refresh abbraccio da parte dell'utente %d", $user->id));


        /**
         * @var Hug $hug
         */
        $hug = Hug::whereId($id)
            ->where('closed_by', '<>', $user->id)->orWhereNull('closed_by')// Se ha chiuso lui l'abbraccio non ha senso che cerca di fare il refresh. Invece può capitare che l'alttro utente lo faccia e si accorge che l'altro ha chiuso l'abbraccio.
            ->firstOrFail();
	    HugLogger::debug("Abbraccio trovato: {$hug->toJson()}");
        HugLogger::setHugId($hug->id);

        // Eseguo il checkIn
        $this->checkIn($hug, $user);

        if (!empty($hug->closed_at)) {
        	$error = "Abbraccio concluso";
	        HugLogger::error($error);
            throw new ExceptionWithCustomCode($error);
        }

        //check whether the user that call the refresh is the seeker ot the sought of the hug
        if ($user->id == $hug->user_seeker_id) {
            $userToHug = User::whereId($hug->user_sought_id)->first();
	        HugLogger::debug("L'utente è il seeker. L'utente trovato è {$userToHug->id}");
        } else {
            $userToHug = User::whereId($hug->user_seeker_id)->first();
	        HugLogger::debug("L'utente è il sought. L'utente trovato è {$userToHug->id}");
        }


        if (($timedOutUserId = $this->otherUserIsAlive($hug, $user)) !== true) {
	        HugLogger::warning("L'altro utente non ha inviato più risposte entro il tempo prestabilito. Chiudo l'abbraccio");
            // l'altro utente non ha inviato il check in entro il massimo ritardo consentito. Chiudo l'abbraccio dichiarando il timeout dell'altro utente
            $hug->close($user, $timedOutUserId);
        }


        return parent::response([
                                    /*
                                     * Secondo me dovremmo passare tutti i dati e sarà l'app a svolgere il resto. Dato che questa funzione verrà chiamata frequentamente conviene
                                     * non passare grandi dati (immgini ecc.)
                                     * Forse conviene solo passare eventuali id di selfie + la posizione aggiornata, (poi l'app aprire get parallele per il fetch)
                                     * In ogni caso solo pochi byte.
                                    */
                                    "user_to_hug_geo_latitude" => $userToHug->geo_latitude,
                                    "user_to_hug_geo_longitude" => $userToHug->geo_longitude,

                                ]);
    }


    /**
     * Funzione per chiudere un abbraccio. Può essere avviato da uno dei due utenti.
     *
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function close(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser();

	    HugLogger::debug(sprintf("Richiesta chiusura abbraccio %d da parte dell'utente %d", $id, $user->id));

        /*
         * @var Hug $hug
         */
        $hug = Hug::whereId($id)->where(function ($query) use ($user) {
            /**
             * @var Builder $query
             */
            $query
                ->where('user_seeker_id', '=', $user->id)
                ->orWhere('user_sought_id', '=', $user->id);
        })->first();

	    HugLogger::setHugId($hug->id);

        if (empty($hug->closed_at)) {
            $hug->close($user);
            HugLogger::debug("Abbraccio chiuso.");
        }

        return parent::response([
                                    "hug" => $hug,
                                ]);
    }

    /**
     * Ritorna i dati dell'abbraccio.
     *
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser();

	    HugLogger::debug(sprintf("Richiesta dati abbraccio %d da parte dell'utente %d", $id, $user->id));
        /**
         * @var Hug $hug
         */
        $hug = Hug::whereId($id)->where(function ($query) use ($user) {
            /**
             * @var Builder $query
             */
            $query
                ->where('user_seeker_id', '=', $user->id)
                ->orWhere('user_sought_id', '=', $user->id);
        })->first();

        return parent::response([
                                    "hug" => $hug,
                                ]);
    }


    /**
     * Richiesta WhoAreYou da parte di uno dei due utenti
     *
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function whoAreYou(Request $request)
    {
        $user = $this->getAuthenticatedUser();

        $this->validate($request, [
            'hug_id' => 'required|numeric'
        ]);

        $hugID = $request->input('hug_id');

	    HugLogger::debug(sprintf("Richiesta 'Who are you' per l'abbraccio %d da parte dell'utente %d", $hugID, $user->id));

        /**
         * @var Hug $hug
         */
        $hug = Hug::whereId($hugID)->where(function ($query) use ($user) {
            /**
             * @var Builder $query
             */
            $query
                ->where('user_seeker_id', '=', $user->id)
                ->orWhere('user_sought_id', '=', $user->id);
        })->firstOrFail();


        if ($hug->user_seeker_id == $user->id && empty($hug->user_seeker_who_are_you_request)) {

            $hug->user_seeker_who_are_you_request = Carbon::now()->toDateTimeString();

        } elseif (empty($hug->user_sought_who_are_you_request)) {

            $hug->user_sought_who_are_you_request = Carbon::now()->toDateTimeString();
        }

        $hug->save();

        if (!empty($hug->user_seeker_who_are_you_request) && !empty($hug->user_sought_who_are_you_request)) {
            $seeker = User::whereId($hug->user_seeker_id)->first();
            $sought = User::whereId($hug->user_sought_id)->first();
            Notifier::send($seeker, 'hug', 'whoAreYou.unlocked', [
               "hug" => $hug,
            ]);
            Notifier::send($sought, 'hug', 'whoAreYou.unlocked', [
                "hug" => $hug,
            ]);
            // Addesso nel fetch dei dati dell'abbraccio ci saranno più dati, questa funzione non deve fare nient'altro.
        }

        return parent::response([
                                    "hug" => $hug,
                                ]);
    }


	/**
	 * Permette all'utente di lasciare un feedback sull'abbraccio.
	 *
	 * @param Request $request
	 * @param         $id
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws ExceptionWithCustomCode
	 */
    public function setFeedback(Request $request, $id)
    {

        $this->validate($request, [
            'feedback' => 'required|numeric|hug.feedback',
        ]);


        $user = $this->getAuthenticatedUser();

        /**
         * @var Hug $hug
         */
        $hug = Hug::whereId($id)->whereNotNull('closed_at')->where(function ($query) use ($user) {
            /**
             * @var Builder $query
             */
            $query
                ->where('user_seeker_id', '=', $user->id)
                ->orWhere('user_sought_id', '=', $user->id);
        })->firstOrFail();


        $feedback = UserHugFeedback::whereUserId($user->id)->whereHugId($hug->id)->first();

        if(!empty($feedback)) {
            // c'è già un feedeback di questo utente
            //TODO: Decideere cosa fare
            //abort(403);
            throw new ExceptionWithCustomCode("Hai già lasciato un feedback", ErrorCode::INVALID_REQUEST, 403);

        }

        $feedback = new UserHugFeedback;
        $feedback->user_id = $user->id;
        $feedback->hug_id = $hug->id;
        $feedback->result = $request->input('feedback');
        $feedback->save();

        return parent::response([]);
    }

    /**
     * Permette di caricare fino ad un massimo di 3 file
     *
     * @param Request $request
     * @param         $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSelfies(Request $request, $id)
    {
        // TODO: Da terminare
        $user = $this->getAuthenticatedUser();

        /**
         * @var Hug $hug
         */
        $hug = Hug::whereId($id)->whereNull('closed_at')->where(function ($query) use ($user) {
            /**
             * @var Builder $query
             */
            $query
                ->where('user_seeker_id', '=', $user->id)
                ->orWhere('user_sought_id', '=', $user->id);
        })->firstOrFail();


        $selfie = new UserHugSelfie;

        $selfie->file_name = 'test.jpg';
        $selfie->hug_id    = $hug->id;
        $selfie->user_id   = $user->id;
        $selfie->file_path = 'test/test2/test.jpg';
        $selfie->file_size = 99121;

        $selfie->save();

        return parent::response([]);
    }

    /**
     * @param Hug  $hug
     * @param User $user
     */
    private function join(Hug $hug, User $user)
    {
        $hug->user_sought_last_checkin = Carbon::now()->toDateTimeString();
        $hug->save();

        $user->status = User::STATUS_HUGGING;
        $user->save();
    }

    /**
     * Serve per rinnovare il checkIn da parte di utente quando l'abbraccio è in corso.
     *
     * @param Hug  $hug
     * @param User $user
     */
    private function checkIn(Hug $hug, User $user)
    {
        if ($user->id == $hug->user_seeker_id) {
            $hug->user_seeker_last_checkin = Carbon::now()->toDateTimeString();
        } else {
            $hug->user_sought_last_checkin = Carbon::now()->toDateTimeString();
        }

        $hug->save();
    }


    /**
     * Controlla se l'altro utente ha inviato un keep alive entro il massimo tempo consentito
     *
     * @param Hug  $hug
     * @param User $user
     *
     * @return bool
     */
    private function otherUserIsAlive(Hug $hug, User $user)
    {
        if ($user->id == $hug->user_seeker_id) {
            $toCheck = 'user_sought_last_checkin';
            $id      = $hug->user_sought_id;
        } else {
            $toCheck = 'user_seeker_last_checkin';
            $id      = $hug->user_seeker_id;
        }

        // Controllo se è troppo vecchio
        if (Carbon::createFromFormat('Y-m-d H:i:s', $hug->$toCheck)->diffInSeconds(Carbon::now(), true) > Hug::MAX_CHECK_IN_DELAY) {
            return $id;
        }

        return true;
    }


    /**
     * Avvisa l'utente che ha accettato l'abbraccio che l'abbraccio è stato creato e che può entrare.
     *
     * @param Hug $hug
     */
    private function alertSoughtUser(Hug $hug)
    {
        $soughtUser = User::whereId($hug->user_sought_id)->first();
        $seekerUser = User::whereId($hug->user_seeker_id)->first();

        Notifier::send($soughtUser, 'hug', 'created', [
            'hug_id'     => $hug->id,
            'search_id'  => $hug->search_id,
            'created_at' => $hug->created_at,
            "seeker_user_geo_latitude" => $seekerUser->geo_latitude,
            "seeker_user_geo_longitude" => $seekerUser->geo_latitude,
        ]);
    }


}