<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Exceptions\ExceptionWithCustomCode;
use App\Helpers\ErrorCode;
use App\Helpers\Loggers\VipLogger;
use App\Helpers\Loggers\VipPostLogger;
use App\Models\PotentialVipUsersList;
use App\Models\User;
use App\Models\Vip;
use App\Models\VipPost;
use App\Models\VipRequest;
use App\Helpers\Notifier;
use App\Helpers\GCMNotification;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mail;
use Illuminate\Database;
use Mockery\Exception;


class VipController extends Controller
{


    //User accepts to be VIP
    public function accept(Request $request)
    {

        $this->validate($request, [
            'vip_request_id' => 'required|exists:vip_requests,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $userID = $request->input('user_id');
        $requestID = $request->input('vip_request_id');
        $user = User::find($userID);

        //check if the user has been selected to be a VIP
        if ($this->isElegibleToVIP($requestID, $userID)) {

            $vipRequest = VipRequest::findOrFail($requestID);
            $vipRequest->accept($userID);
            VipLogger::debug("L'utente {$userID} ha accettato la proposta di diventare il VIP del mese..");

            $vip = new Vip;
            $vip->makeVip($userID);
            VipLogger::debug("L'utente {$userID} è il nuovo VIP per il prossimo mese.");

            // Invio email
            Mail::send('emails.vip', ['user' => $user], function ($message) use ($request) {
                /**
                 * @var \Illuminate\Mail\Message $message
                 */
                $message
                    ->from(Config::get('mail.from.address'), Config::get('mail.from.name'))
                    ->to("luca.cricchio@gmail.com")
                    ->subject('VIP title');
            });

            return parent::response([
                'success' => true,
            ]);
        } else {
            throw new ExceptionWithCustomCode("Non puoi rispondere a questa richiesta", ErrorCode::INVALID_REQUEST, 403);
        }
    }


    //User declines to be VIP
    public function decline(Request $request)
    {

        $this->validate($request, [
            'vip_request_id' => 'required|exists:vip_requests,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $userID = $request->input('user_id');
        $requestID = $request->input('vip_request_id');


        //check if the user has been selected to be a VIP
        if ($this->isElegibleToVIP($requestID, $userID)) {

            $vipRequest = VipRequest::whereId($requestID)->first();
            $vipRequest->decline($userID);
            VipLogger::debug("L'utente {$userID} ha rifiutato la proposta di diventare il VIP del mese..");


            $this->sendNextVipProposal($vipRequest);

            return parent::response([
                'success' => true,
            ]);
        } else {
            throw new ExceptionWithCustomCode("Non puoi rispondere a questa richiesta", ErrorCode::INVALID_REQUEST, 403);
        }

    }

    //sending notification to potential vip user
    public function sendNotification(VipRequest $request)
    {
        $user = User::find($request->user_id);

        $data = [
            'vip_request_id'  => $request->id,
        ];

        Notifier::send($user, "vip", "notifyVip", $data, "VIP request", "You have been selected to be a VIP");
        VipLogger::debug("Notifica inviata all'utente {$user->id}");

        $request->fetched_at = Carbon::now()->toDateTimeString();
        $request->save();
    }

    //check if user is elegible to vip (a request mush have been launched by the server first)
    public function isElegibleToVIP($requestID, $userID)
    {

        try {
            VipRequest::whereUserId($userID)
                ->whereId($requestID)
                ->whereNotNull('fetched_at')
                ->where('response_type' , '=', 0)
                ->firstOrFail();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    //create an ordered list of users (by feedbacks result) of a certain month/year
    //call with a cron
    public function createMonthVipList(Request $request)
    {
        //rimuovo gli eventuali Vip del mese precedente
        $this->removeCurrentVipUsers();

        //prendo il mese passato (il mese del server -1, dato che il cron verrà eseguito i primi giorni del mese successivo)
        // e l'anno dal server
        $now = Carbon::now()->subMonth();
        $year = $now->year;
        $month = $now->month;

        //todo: solo per i test, da eliminare in seguito
        if($request->get('year') > 0  && $request->get('month'))
        {
            $year =$request->get('year');
            $month = $request->get('month');
        }

        VipLogger::setYearAndMonth($year,$month);

        //controllo se è stata già generata (anno/mese)
        $potentialVipUsersList = PotentialVipUsersList::where('month', $month)
                                                        ->where('year', $year)
                                                        ->first();
        if ($potentialVipUsersList instanceof PotentialVipUsersList) {
            $errorMessage = "La lista di questo mese è già stata creata.";
            VipLogger::error($errorMessage);
            throw new ExceptionWithCustomCode($errorMessage, ErrorCode::INVALID_REQUEST, 403);
        }

        VipLogger::debug("Creo istanza lista potenziali vip...");
        $potentialVipUsersList = new PotentialVipUsersList();
        $potentialVipUsersList->year = $year; //$request->get('year');
        $potentialVipUsersList->month = $month;
        $potentialVipUsersList->save();


        $potentialVipUsersList->createPreviousMonthList();

        return parent::response([
            'success'    => true,
        ]);
    }

    /**
     * prendo il prossimo utente da contattare  dalla lista dei potenziali utenti ed invio la richiesta
     *
     * @param VipRequest $vipRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws ExceptionWithCustomCode
     */
    public function sendNextVipProposal(VipRequest $vipRequest){

        $now = Carbon::now()->subMonth();
        $year = $now->year;
        $month = $now->month;

        VipLogger::setYearAndMonth($year, $month);
        VipLogger::debug('Inizio ricerca prossimo utente da contattare..');

        $currentPotentialListId = $this->getCurrentPotentialUsersListId();

        VipLogger::debug('Ricavo lista utenti da contattare..');
        $vipRequests = VipRequest::wherePotentialUsersListId($currentPotentialListId)
                                ->whereNull('fetched_at')
                                ->whereResponseType(0)
                                ->where('positive_feedbacks', DB::raw("(select max(`positive_feedbacks`) from vip_requests where response_type = 0)"))
                                ->get();


        if(empty($vipRequests)){
            $message = "Nessun utente da contattare.";
            VipLogger::debug($message);

            return parent::response([
                'success'    => true,
                'message'    => $message
            ]);
        }


        if($this->pendingRequests($currentPotentialListId) === true){
            $message = "In attesa della risposta dei potenziali utenti VIP già contattati. Aspettare il tempo limite di 24 ore";
            VipLogger::debug($message);

            return parent::response([
                'success'    => true,
                'message'    => $message
            ]);
        }


        //invio proposta
        VipLogger::debug('Invio notifica/e..');
        foreach($vipRequests AS $vipRequest) {
            $this->sendNotification($vipRequest);
        }


        return parent::response([
            'success'    => true,
        ]);
    }

    /**
     * todo: completare
     * check if there are any pending request that not passed 24 hours
     */
    private function pendingRequests($currentPotentialListId){

        $pendingRequest = VipRequest::wherePotentialUsersListId($currentPotentialListId)
                    ->whereResponseType(0)
                    ->whereNotNull('fetched_at')
                    ->orderBy('positive_feedbacks')
                    ->first();





        //if pending requests have passed more than 24 hours return false
        if (empty($pendingRequest) || $pendingRequest->fetched_at < Carbon::now()->subDay()->toDateTimeString()){
            return false;
        }

        return true;
    }


    /**
     * @return mixed
     * @throws ExceptionWithCustomCode
     */
    private function getCurrentPotentialUsersListId(){

        $prevMonth = Carbon::now()->subMonth();
        $year = $prevMonth->year;
        $month = $prevMonth->month;

        $list = PotentialVipUsersList::where('year', $year)
                                        ->where('month', $month)
                                        ->first();


        if(empty($list)){
            $errorMessage = "Impossibile ottenere lista, non è stata ancora creata";
            VipLogger::error($errorMessage);
            throw new ExceptionWithCustomCode($errorMessage, ErrorCode::INVALID_REQUEST, 403);
        }

        return $list->id;

    }


    //get currents VIPS and their activities
    public function getCurrentVipActivities(){

        $vips = self::getActiveVips();
        $vipActivities = [];

        foreach($vips AS $vip) {

            //load vip posts
            $vip->posts;
            //load vip user
            $vip->user;

            $vipActivities[] = $vip;
        }

        return parent::response([
                "vipActivities" => $vipActivities,
            ]
        );

    }


    /* get currents VIP
     * @params $onlyUserIds if true, returns an array with only users Id
     */
    private static function getActiveVips($onlyUserIds = false){

        if($onlyUserIds === true){
            return Vip::whereActive(Vip::STATUS_ACTIVE)
                ->orderBy('created_at', 'desc')->pluck('user_id')->toArray();
        }else {
            return Vip::whereActive(Vip::STATUS_ACTIVE)
                ->orderBy('created_at', 'desc')->get();
        }
    }



    //check if an user is currently a Vip
    public static function isVip($userId)
    {
        $vipList = self::getActiveVips(true);
        if (in_array($userId, $vipList))
            return true;
        else
            return false;
    }

    //todo: da chiedere ad Andrea come preferisce passarmi i contenuti
    public function makePublicPost(Request $request){

        $this->validate($request, [
            'text' => 'sometimes|max:255',
            'image' => 'sometimes|max:2048|mimes:jpeg,jpg,png',
            'video_link' => 'sometimes',
        ]);


        $user = $this->getAuthenticatedUser();

        VipPostLogger::debug("Invio post da parte dell'utente: {$user->id}..");

        if(!$this->isVip($user->id)){
            $errorMessage = "L'utente {$user->id} non è vip, non può quindi pubblicare post.";
            VipPostLogger::error($errorMessage);
            throw new ExceptionWithCustomCode($errorMessage, ErrorCode::USER_NOT_AUTHORISED, 403);
        }

        $vip = Vip::whereUserId($user->id)
                    ->whereActive(Vip::STATUS_ACTIVE)->first();

        if(!$vip){
            $errorMessage = "Utente vip non trovato: user {$user->id}.";
            VipPostLogger::error($errorMessage);
            throw new ExceptionWithCustomCode($errorMessage, ErrorCode::INVALID_REQUEST, 403);
        }

        // Salvataggio del file
        $post = new VipPost();
        $post->vip_id = $vip->id;

        if($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->hashName(sprintf("vips/images"));
            $disk = \Storage::disk('local');
            $disk->put($path, file_get_contents($image));

//            $post->file_name = $image->getClientOriginalName();
            $post->image_path = $path;
//            $post->file_size = $image->getClientSize();
        }


        $post->content = $request->input('content');
        $post->video_link = $request->input('video_link');

        $post->save();
        VipPostLogger::debug(sprintf("Post salvato: %s", $post->toJson()));


        return parent::response([
            'success'    => true,
        ]);
    }

    public function removeCurrentVipUsers(){
        VipLogger::debug("Rimuovo tutti i vip attuali..");
        Vip::whereActive(Vip::STATUS_ACTIVE)->update(['active' => Vip::STATUS_INACTIVE]);
        VipLogger::debug("Vip rimmossi.");

    }

    public function getPostImage(Request $request){

        $this->validate($request, [
            'post_id' => 'required|exists:vip_posts,id',
        ]);

        VipPostLogger::setPostId($request->get('post_id'));
        VipPostLogger::debug("Richiesta immagine post.");
        $vipPost = VipPost::whereId($request->get('post_id'))->firstOrFail();


        return response()->download(\Storage::disk('local')->getDriver()->getAdapter()->applyPathPrefix($vipPost->image_path));
    }

}
