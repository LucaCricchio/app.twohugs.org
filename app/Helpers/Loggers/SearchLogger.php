<?php
/**
 * Created by PhpStorm.
 * User: C47
 * Date: 03/12/2016
 * Time: 19:46
 */

namespace App\Helpers\Loggers;

/**
 * Class AsyncTimestampLogger
 *
 * @package App\Helpers\Loggers
 */
class SearchLogger extends Logger
{
	protected static $searchId   = 0;
	protected static $loggerName = 'search';

	protected static function config()
	{
		static::$path = storage_path() . '/logs/search.log';
	}

	public static function setSearchId($searchId)
	{
		static::$searchId = $searchId;
	}

	protected static function addEntry($level, $entry)
	{
		if ( static::$searchId > 0) {
			$entry = '[ID: ' . static::$searchId . '] - ' . $entry;
		}
		parent::addEntry($level, $entry);
	}
}