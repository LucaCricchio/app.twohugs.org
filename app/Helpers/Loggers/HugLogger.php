<?php
/**
 * Created by PhpStorm.
 * User: C47
 * Date: 03/12/2016
 * Time: 19:46
 */

namespace App\Helpers\Loggers;

/**
 * Class HugLogger
 *
 * @package App\Helpers\Loggers
 */
class HugLogger extends Logger
{
	protected static $hugId   = 0;
	protected static $loggerName = 'hugs';

	protected static function config()
	{
		static::$path = storage_path() . '/logs/hugs.log';
	}

	public static function setHugId($hugId)
	{
		static::$hugId = $hugId;
	}

	protected static function addEntry($level, $entry)
	{
		if ( static::$hugId > 0) {
			$entry = '[ID: ' . static::$hugId . '] - ' . $entry;
		}
		parent::addEntry($level, $entry);
	}
}