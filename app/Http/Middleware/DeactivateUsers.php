<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;

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
            $user->status = User::STATUS_AVAILABLE;
            $user->save();
        }
        $minutesAgo = Carbon::now()->subMinutes(30);
        \DB::update("UPDATE users SET status='0' WHERE users.geo_last_update < {$minutesAgo}");

        return $next($request);
    }
}
