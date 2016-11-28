<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Exceptions\ExceptionWithCustomCode;
use App\Helpers\ErrorCode;
use App\Models\User;
use App\Models\Vip;
use App\Models\VipRequest;
use App\Helpers\Notifier;
use App\Helpers\GCMNotification;
use Config;
use Illuminate\Http\Request;
use Mail;
use Illuminate\Database;


class VipController extends Controller
{

    //User accept to be VIP
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

            $vip = new Vip;
            $vip->makeVip($userID);

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


    //todo: da continuare
    //User decline to be VIP
    public function decline(Request $request)
    {

        $this->validate($request, [
            'vip_request_id' => 'required|exists:vip_requests,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $userID = $request->input('user_id');
        $requestID = $request->input('vip_request_id');
        //$user = User::find($userID);


        //check if the user has been selected to be a VIP
        if ($this->isElegibleToVIP($requestID, $userID)) {

            $vipRequest = VipRequest::findOrFail($requestID);
            $vipRequest->decline($userID);

            return parent::response([
                'success' => true,
            ]);
        } else {
            throw new ExceptionWithCustomCode("Non puoi rispondere a questa richiesta", ErrorCode::INVALID_REQUEST, 403);
        }

    }

    //testing purpose, we will pass user A data too here
    public function sendNotification($userID)
    {
        $user = User::find($userID);
        //in $data ci saranno i dati da passare al client
        $data = [
            'test_data_field_1'  => "field_1",
            'test_data_field_2'  => "field_2",
            'test_data_field_3'  => "field_3",
        ];
        Notifier::send($user, "vip", "notifyVip", $data, "VIP request", "You have been selected to be a VIP");

    }

    //check if user is elegible to vip (a request mush have been launched by the server first)
    public function isElegibleToVIP($requestID, $userID)
    {

        try {
            VipRequest::whereUserId($userID)
                ->whereId($requestID)
                ->where('response_type' , '=', 0)
                ->firstOrFail();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    //return an ordered list of users (by feedbacks result) of a certain month/year
    public function getMonthVipList(Request $request)
    {

        $this->validate($request, [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2016,3000'
        ]);


        //todo: da continuare
        $vipList =
            \DB::table('user_hug_feedbacks')
                ->select('user_id', 'users.username', \DB::raw('sum(result) as feedback_result'))
                ->join('hugs', 'user_hug_feedbacks.hug_id', '=', 'hugs.id')
                ->join('users', 'users.id', '=', 'hugs.user_seeker_id')
                ->whereMonth('hugs.created_at', "=", $request->get('month'))
                ->whereYear('hugs.created_at', "=", $request->get('year'))
                ->groupBy('user_id')
                ->orderBy('feedback_result', 'desc')
                ->limit(10)
                ->get();

        return parent::response([
            "vipList" => $vipList]);


    }





}
