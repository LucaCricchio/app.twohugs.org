<?php
namespace App\Helpers\Loggers;

/**
 * Class StatusLogger
 *
 * @package App\Helpers\Loggers
 */
class StatusLogger extends Logger
{
    protected static $userId     = 0;
    protected static $userStatus = -1;
    protected static $loggerName = 'statuses';

    protected static function config()
    {
        static::$path = storage_path() . '/logs/statuses.log';
    }

    public static function setUserData($user, $status)
    {
        static::$userId = $user;
        static::$userStatus = $status;
    }

    protected static function addEntry($level, $entry)
    {
        if ( static::$userId > 0 ) {
            $entry = '[ID: ' . static::$userId . ', Status: '. static::$userStatus .'] - ' . $entry;
        }
        parent::addEntry($level, $entry);
    }
}