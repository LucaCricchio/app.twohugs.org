<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
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
            ]);
        }
        return parent::response([
            'success'    => true,
            'user'       => $user,
        ]);
    }

}