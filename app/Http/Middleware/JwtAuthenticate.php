<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtAuthenticate
{


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*if(!$request->is("auth/login") && !$request->is('user/register')) {
            $user = null;
            try {
                if (! $user = JWTAuth::parseToken()->authenticate()) {
                    // Autenticato

                }
            } catch (TokenExpiredException $e) {
                // Token Scaduto

            } catch (TokenInvalidException $e) {
                // Token invalido

            } catch (JWTException $e) {
                // Token non presente
            }

            if(!($user instanceof User && $user->exists)) {
                abort(403);
            }
        }*/

        // TODO: Ad ogni interazione col server l'utente passera la propria posizione.

        return $next($request);
    }
}
