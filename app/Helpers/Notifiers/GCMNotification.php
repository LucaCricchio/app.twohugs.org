<?php

namespace App\Helpers\Notifiers;

use App\Contracts\NotifierContract;
use PushNotification;
use App\Models\User;


class GCMNotification implements NotifierContract
{

    public static function send(User $user, $category, $action, $body, $title = "", $message = "")
    {
        $notification = [
            'category' => $category,
            'action'   => $action,
            'body'     => $body,
        ];

        self::sendNotification($user->gcm_device_id, $title, $message, $notification);
    }

    protected static function sendNotification($deviceToken, $title, $message, $data)
    {

        $devices = PushNotification::DeviceCollection([
                                                          PushNotification::Device($deviceToken),
                                                      ]);

        $notificationMessage = PushNotification::Message($message, [
            'title' => $title,
            'data'  => $data,
        ]);


        $collection = PushNotification::app('twoHugsAndroid')
            ->to($devices)
            ->send($notificationMessage);

        $response = null;

        // get response for each device push
        foreach ($collection->pushManager as $push) {
            $response = $push->getAdapter()->getResponse();
        }

        // access to adapter for advanced settings
        $push = PushNotification::app('twoHugsAndroid');
        $push->adapter->setAdapterParameters(['sslverifypeer' => false]);

        return 1;
    }

}