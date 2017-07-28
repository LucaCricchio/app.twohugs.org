<?php

namespace App\Http\Middleware;

use App\Helpers\Loggers\StatusLogger;
use DB;
use Closure;
use Carbon\Carbon;
use App\Models\User;

class DeactivateUsers
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
        if (($user = $request->user()) != null) {
            /**
             * @var $user User
             */
            StatusLogger::setUserData($user->id, $user->status);
            StatusLogger::debug("Current route: {$request->route()}");
            if ($user->status == User::STATUS_NOT_AVAILABLE) {
                $user->status = User::STATUS_AVAILABLE;
                $user->save();
            }
        }
        $minutesAgo = Carbon::now()->subMinutes(30);
        DB::update("UPDATE users SET status='0' WHERE users.geo_last_update < '{$minutesAgo}'");

        return $next($request);
    }
}
