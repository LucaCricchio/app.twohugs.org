<?php
namespace App\Helpers\Loggers;

class StatusLogger extends Logger
{
    protected static $loggerName = "status";

    protected static function config()
    {
        static::$path = storage_path() . '/logs/status.log';
    }
}