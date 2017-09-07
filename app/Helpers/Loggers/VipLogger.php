<?php

namespace App\Helpers\Loggers;

/**
 * Class VipLogger
 *
 * @package App\Helpers\Loggers
 */
class VipLogger extends Logger
{
	protected static $year   = 0;
    protected static $month   = 0;
	protected static $loggerName = 'vip';

	protected static function config()
	{
		static::$path = storage_path() . '/logs/vip.log';
	}

    public static function setYearAndMonth($year, $month)
    {
        static::$year = $year;
        static::$month = $month;
    }

	protected static function addEntry($level, $entry)
	{
        if ( (static::$year > 0) && (static::$month > 0)) {
            $entry = '[Year: ' . static::$year . ' | Month: '. static::$month . '] - ' . $entry;
        }
		parent::addEntry($level, $entry);
	}
}