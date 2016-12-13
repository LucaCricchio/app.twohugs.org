<?php

namespace App\Contracts;

use App\Models\User;

interface NotifierContract
{

    public static function send(User $user, $category, $action, $body, $title = "", $message = "");

}