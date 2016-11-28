<?php

namespace App\Helpers;

use App\Contracts\NotifierContract;
use App\Models\User;


class Notifier implements NotifierContract
{

    private static $notifier = "App\\Helpers\\Notifiers\\GCMNotification";

    public static function send(User $user, $category, $action, $body, $title = "", $message = "")
    {
        return call_user_func_array([static::$notifier, "send"], func_get_args());
    }

}