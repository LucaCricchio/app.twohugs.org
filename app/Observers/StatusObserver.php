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
        $log .= "Request came at: {$clockTime}\n";
        $log .= "Request route: {$currentRequest->path()}\n";
        $log .= "Request user: {$currentRequest->user()->id}\n";
        $log .= "User id: {$user->id}\n";
        $log .= "User status: {$user->status}\n";
        $log .= "********************************\n";

        StatusLogger::info($log);
    }
}