<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Exceptions\ExceptionWithCustomCode;
use App\Helpers\ErrorCode;
use App\Helpers\Loggers\VipLogger;
use App\Models\PotentialVipUsersList;
use App\Models\User;
use App\Models\Vip;
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

            $vipRequest = VipRequest::findOrFail($requestID)->first();
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
        //todo: da chiedere ad Andrea se potrebbe servire qualche dato
        $data = [
            'test_data_field_1'  => "field_1",
            'test_data_field_2'  => "field_2",
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
        //prendo il mese passato (il mese del server -1, dato che il cron verrà eseguito i primi giorni del mese successivo)
        // e l'anno dal server
        $now = Carbon::now()->subMonth();
        $year = $now->year;
        $month = $now->month;

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

        //todo: gestire il caso in cui i potenziali vip sono più di un utente
        $vipRequest = VipRequest::wherePotentialUsersListId($vipRequest->potential_users_list_id)
                                ->whereNull('fetched_at')
                                ->orderBy('positive_feedbacks', 'desc')
                                ->first();

        if(empty($vipRequest)){
            $message = "Nessun utente da contattare.";
            VipLogger::debug($message);

            return parent::response([
                'success'    => true,
                'message'    => $message
            ]);
        }

        try {
            $user = User::findOrFail($vipRequest->user_id);
        } catch (\Exception $e) {
            $errorMessage = "Errore richiesta VIP: lista non generata o l'utente non è stato trovato.";
            VipLogger::error($errorMessage);
            throw new ExceptionWithCustomCode($errorMessage, ErrorCode::INVALID_REQUEST, 403);
        }

        if(!$this->isElegibleToVIP($vipRequest->id, $user->id)){
            $errorMessage = "Attualmente questo utente non può diventare VIP - user: {$user->id}";
            VipLogger::error($errorMessage);
            throw new ExceptionWithCustomCode($errorMessage, ErrorCode::INVALID_REQUEST, 403);
        }

        //invio proposta
        VipLogger::debug('Invio notifica..');
        $this->sendNotification($vipRequest);


        return parent::response([
            'success'    => true,
        ]);
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
            return Vip::whereActive(1)
                ->orderBy('created_at', 'desc')->pluck('user_id')->toArray();
        }else {
            return Vip::whereActive(1)
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

}
