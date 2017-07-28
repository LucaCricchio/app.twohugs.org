<?php

namespace App\Http\Middleware;

use DB;
use Closure;
use Carbon\Carbon;
use App\Models\User;
use Log;

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
            Log::debug("*****\nUser ID: {$user->id}\nUser status: {$user->status}\nRoute: {$request->route()}\n*****");
            if ($user->status == User::STATUS_NOT_AVAILABLE) {
                $user->status = User::STATUS_AVAILABLE;
                $user->save();
            }
        }
        $minutesAgo = Carbon::now()->subMinutes(30)->toDateTimeString();
        DB::update("UPDATE users SET status='0' WHERE users.geo_last_update < '{$minutesAgo}'");

        return $next($request);
    }
}
