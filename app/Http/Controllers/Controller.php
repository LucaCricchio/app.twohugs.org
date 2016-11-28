<?php

namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use App\Helpers\ErrorCode;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use JWTAuth;
use PhpSpec\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Validator;
use Zend\Uri\Http;

abstract class Controller extends BaseController
{


    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;



    /**
     * Validate the given request with the given rules.
     *
     * @param  Request $request
     * @param  array   $rules
     * @param  array   $messages
     * @param  array   $customAttributes
     *
     * @throws ValidationException
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = Validator::make($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $exception = new ValidationException();
            $exception->setErrors($validator->errors());
            throw $exception;
        }
    }

    /**
     * @return \App\Models\User|null
     * @throws Exception
     */
    public function getAuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                // Utente non trovato
                throw new Exception("User not found");
            }

            return $user;
        } catch (TokenExpiredException $e) {
            // Token Scaduto
            return $this->response([], [
                "token expired",
            ]);

        } catch (TokenInvalidException $e) {
            // Token invalido
            return $this->response([], [
                "invalid",
            ]);

        } catch (JWTException $e) {
            // Token non presente
            return $this->response([], [
                "token not found",
            ]);
        }

        return null;
    }

    /**
     * Conforme per quanto possibile a http://jsonapi.org/format/
     *
     * @param       $data
     * @param array $errors
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function response($data, $statusCode = 200)
    {
        $response = [
            'jsonapi' => [
                'version' => config('app.api_version')
            ],
            'data'    => $data,
        ];


        return response()->json($response)->setStatusCode($statusCode);
    }


}
