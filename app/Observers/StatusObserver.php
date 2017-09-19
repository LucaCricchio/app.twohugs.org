<?php
namespace App\Observers;

use App\Helpers\Loggers\StatusLogger;
use App\Models\User;
use Carbon\Carbon;

class StatusObserver
{
    public function updated(User $user)
    {
        $clockTime = Carbon::now();
        $currentRequest = request();

        $log = "********************************\n";
        $log .= "Request came at: {$clockTime}";
        $log .= "Request route: {$currentRequest->route()->getName()}\n";
        $log .= "Request user: {$currentRequest->user()}\n";
        $log .= "User id: {$user->id}\n";
        $log .= "User status: {$user->status}\n";
        $log .= "********************************\n";

        StatusLogger::info($log);
    }
}