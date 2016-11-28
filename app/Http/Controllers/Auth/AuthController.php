<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ExceptionWithCustomCode;
use App\Exceptions\ValidationException;
use App\Helpers\ErrorCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;
use App\Http\Controllers\ProfileController;

class AuthController extends Controller
{

    public function login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);


        $token = null;
        try {
            if ( ! $token = JWTAuth::attempt($request->only('email', 'password'))) {
                throw new ExceptionWithCustomCode(trans('auth.failed'), ErrorCode::LOGIN_FAILED, 400);
            }
        } catch (JWTException $e) {
            abort(500, 'Could not create token');
        }

        $user = User::whereEmail($request->input('email'))->first();

        return $this->processLogin($user, $token);
    }

    public function loginWithFacebook(Request $request)
    {

        $this->validate($request, [
            'facebook_id' => 'required|numeric',
            'email' => 'required|email|unique:users,email,' . $request->input('facebook_id') . ',facebook_user_id',
        ]);


        $token = null;
        $user  = null;
        try {
            $user = User::whereFacebookUserId($request->input('facebook_id'))->orWhere('email', '=', $request->input('email'))->first();

            if($user === null) {
                $user = new User;
            }

            $user->facebook_user_id = $request->input('facebook_id');
            $user->email = $request->input('email');
            $user->save();
            $token = JWTAuth::fromUser($user);


        } catch (JWTException $e) {
            abort(500, 'Could not create token');
        }

        return $this->processLogin($user, $token);
    }

    public function loginWithGooglePlus(Request $request)
    {

        $this->validate($request, [
            'google_id' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->input('google_id') . ',google_user_id',
        ]);


        $token = null;
        $user  = null;
        try {
            $user = User::whereGoogleUserId($request->input('google_id'))->orWhere('email', '=', $request->input('email'))->first();

            if($user === null) {
                $user = new User;

            }

            $user->google_user_id = $request->input('google_id');
            $user->email = $request->input('email');
            $user->save();

        } catch (JWTException $e) {
            abort(500, 'Could not create token');
        }

        return $this->processLogin($user, $token);
    }

    public function activate($code, Request $request)
    {
        if(($user = User::whereActivationCode($code)->first()) === null) {
            abort(404, 'User not found');
        }
        /**
         * @var User $user
         */
        $user->activation_code = null;
        $user->activation_date = Carbon::now()->toDateTimeString();
        $user->save();

        // TODO: Dovremmo ritornare una view minimale con scritto utente attivato [sarebbe utile non fargli vdere che arriva sul dominio "api.twohugs.com". Dovremmo pensarci
        return 'Utente attivato';
    }

    protected function processLogin(User $user, $token)
    {
        if(!empty($user->activation_code)) {
            return parent::response([
                'success' => false,
                'error'   => 'Your account is not active',
            ]);
        }

        if($user->blocked) {
            return parent::response([
                'success' => false,
                'error'   => 'Your account is blocked',
            ]);
        }

        $user->last_login = Carbon::now();
        $user->save();

        return parent::response([
            'token'   => $token,
            'user'    => $user,
        ]);
    }
}
