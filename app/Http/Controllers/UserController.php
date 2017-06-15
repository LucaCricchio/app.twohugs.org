<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Exceptions\JsonResponseError;
use App\Models\User;
use App\Models\Hug;
use App\Models\UserFriend;
use App\Helpers\Notifier;
use Carbon\Carbon;
use Config;
use Illuminate\Http\Request;
use Mail;

class UserController extends Controller
{

    public function register(Request $request)
    {
            $this->validate($request, [
                'email'      => 'required|email|unique:users,email',
                'password'   => 'required',
            ]);

            $data = $request->all();
            $user = new User;
            $user->register($data);

            // Invio email
            Mail::send('emails.registration', ['user' => $user], function($message) use ($request)
            {
                /**
                 * @var \Illuminate\Mail\Message $message
                 */
                $message
                    ->from(Config::get('mail.from.address'), Config::get('mail.from.name'))
                    ->to($request->input('email'))
                    ->subject('Twohugs registration: Activate you account');
            });


        return parent::response([
            'success'    => true,
        ]);
    }



    public function changeStatus(Request $request)
    {
        try {
            $this->validate($request, [
                'status' => 'required|numeric|min:0|max:4'
            ]);
            $user = $this->getAuthenticatedUser();
            $user->status = $request->input('status');
            $user->save();
        } catch(ValidationException $e) {
            $errors = $e->getErrors();
            return parent::response([
                'validation' => false,
                'errors'     => $errors,
            ]);
        }
        return parent::response([
            'success'    => true,
        ]);
    }


    public function updatePosition(Request $request)
    {
            $this->validate($request, [
                'geo_latitude'  => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/',
                'geo_longitude' => 'required|regex:/^(-)?[0-9]{1,3}\.[0-9]{1,7}+$/'
            ]);
            $user = $this->getAuthenticatedUser();
            $user->geo_latitude = $request->input('geo_latitude');
            $user->geo_longitude = $request->input('geo_longitude');
            $user->geo_last_update = Carbon::now()->toDateTimeString();
            $user->save();

        return parent::response([
            'success'    => true,
        ]);
    }

    //todo: da completare || l'aveva fatta già Francesco
    public function sendWhoAreYouRequest(Request $request){

        $this->validate($request, [
            'hug_id' => 'required|numeric'
        ]);

        $hugID = $request->input('hug_id');

        $user = $this->getAuthenticatedUser();

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



        //setta campo nel server, controllo se è il seeker o il sought
        if($user->id == $hug->user_seeker_id) {
            $hug->user_seeker_who_are_you_request = Carbon::now()->toDateTimeString();
        } elseif ($user->id == $hug->user_sought_id) {
            $hug->user_sought_who_are_you_request = Carbon::now()->toDateTimeString();
        } else {

        }

        $hug->save();

        //controlla se l'utente corrispondente abbia fatto una richiesta reciproca entro 24 h
        //se entrambi hanno fatto richiesta, invia notifica ad entrambi e sblocca profili (li rende amici)
        if($hug->user_seeker_who_are_you_request > 0 && $hug->user_sought_who_are_you_request > 0){

            $seekerUser = User::whereId($hug->user_seeker_id)->first();
            $soughtUser = User::whereId($hug->user_sought_id)->first();

            $data = array();
            Notifier::send($seekerUser, "You got a new Friend!", "A new user has accepted you as a Friend", $data);
            Notifier::send($soughtUser, "You got a new Friend!", "YA new user has accepted you as a Friend", $data);


            //salvo l'amicizia
            $userFriend = new UserFriend();
            $userFriend->user_id = $seekerUser->id;
            $userFriend->friend_ud = $soughtUser->id;
            $userFriend->created_at = Carbon::now()->toDateTimeString();

            $userFriend->save();
        }

        return parent::response([
            'success'    => true,
        ]);

    }

    //set user GCM token
    public function setGCMToken(Request $request)
    {
        $this->validate($request, [
            'gcm_registration_id' => 'required'
        ]);

        $user = $this->getAuthenticatedUser();

        $user->gcm_device_id = $request->input('gcm_registration_id');;
        $user->save();

        return parent::response([
            'success'    => true,
        ]);

    }

    //get user by id
    public function get($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (\Exception $e){

            return parent::response([
                'success'    => false,
                'error'      => "User not found",
            ]);
        }

        return parent::response([
            'success'    => true,
            'user'     => $user,
        ]);
    }


    //get all users
    public function getAll()
    {
        $users = User::all()->take(20);

        return parent::response([
            'success'    => true,
            'users'     => $users,
        ]);

    }

    //todo: to remove
    public function testSetAvailable()
    {
        $andrea = User::whereId(1023)->first();
        $andrea->status = 1;
        $andrea->save();

        $luca = User::whereId(1021)->first();
        $luca->status = 1;
        $luca->save();

        return parent::response([
            'success'    => true,
        ]);

    }

}
