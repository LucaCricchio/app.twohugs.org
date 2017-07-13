<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Exceptions\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Validator;

class ProfileController extends Controller
{
    public function get()
    {
        $user = $this->getAuthenticatedUser();
        /**
         * @var User $user
         */
        return parent::response([
            "user" => $user
        ]);
    }

    public function update(Request $request)
    {
        try {
            $this->validate($request, [
                'email'      => 'sometimes|required|email|unique:users,email',
                'first_name' => 'sometimes|required',
                'last_name'  => 'sometimes|required',
                'birth_date' => 'sometimes|required|birth_date',
                'country'    => 'sometimes|required|exists:countries,id',
                'city'       => 'sometimes|required',
                'gender'     => 'sometimes|required|regex:/^[MF]$/',
                'address'    => 'sometimes|required',
                'zipcode'    => 'sometimes|required|regex:/^[0-9]+$/',
                //'parent_email' => 'sometimes|unique:users,parent_email'
            ]);
            $user = $this->getAuthenticatedUser();

            $data = $request->all();
            foreach($data AS $key => $value) {
                $user->$key = $request->input($key);
            }


            $user->save();
        } catch(ValidationException $e) {
            $errors = $e->getErrors();
            return parent::response([
                'validation' => false,
                'errors'     => $errors,
            ], \Symfony\Component\HttpFoundation\Response::HTTP_NOT_ACCEPTABLE);
        }
        return parent::response([
            'success'    => true,
            'user'       => $user,
        ]);
    }

    //imposta l'avatar di un utente
    public function setAvatar(Request $request){

        $user = $this->getAuthenticatedUser();

        $this->validate($request, [
            'avatar' => 'required',
        ]);

        $image = $request->avatar;

        $image->store('/users/'.$user->id.'/avatar/');

        return parent::response([
            'success'    => true,
        ]);
    }

    //restituisce l'immagine del profilo di un utente
    public function getAvatar()
    {
        $user = $this->getAuthenticatedUser();

        $avatar =   \Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix().'/users/'.$user->id.'/avatar/'.$user->avatar;

        return response()->download($avatar);
    }

    public function changePasswordOnTheFly(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email'
        ]);
        $user = User::whereEmail($request->get('email'))->firstOrFail();
        $password = uniqid("2hg_", true);
        $user->password = \Hash::make($password);
        \Mail::send('emails.passwordChange', [
            'user' => $user,
            'password' => $password
        ], function (\Illuminate\Mail\Message $message) use($user) {
            $from = \Config::get('mail.from');
            $message->from($from['address'], $from['name']);
            $message->to($user->email, $user->first_name . " " . $user->last_name);
            $message->subject('Password change');
        });
        return "Your new password is in your inbox.";
    }
}