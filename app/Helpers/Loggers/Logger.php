<?php
/**
 * Created by PhpStorm.
 * User: C47
 * Date: 03/12/2016
 * Time: 19:46
 */

namespace App\Helpers\Loggers;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonoLogger;

/**
 * Class Logger
 *
 * @method static debug($message)
 * @method static info($message)
 * @method static notice($message)
 * @method static warning($message)
 * @method static error($message)
 * @method static critical($message)
 * @method static alert($message)
 * @method static emergency($message)
 * @package App\Helpers\Loggers
 */
class Logger
{
	protected static $path;
	protected static $minLevel = MonoLogger::DEBUG;

	protected static $levels = [
		'debug'     => MonoLogger::DEBUG,
		'info'      => MonoLogger::INFO,
		'notice'    => MonoLogger::NOTICE,
		'warning'   => MonoLogger::WARNING,
		'error'     => MonoLogger::ERROR,
		'critical'  => MonoLogger::CRITICAL,
		'alert'     => MonoLogger::ALERT,
		'emergency' => MonoLogger::EMERGENCY,
	];

	/**
	 * @var MonoLogger $logger
	 */
	protected static $logger = null;
	protected static $loggerName = 'DefaultLogger';

	protected static function init()
	{
		static::config();
		$logger = new MonoLogger(static::$loggerName);

		$handler = new RotatingFileHandler(static::$path, 0, static::$minLevel);
		$handler->setFormatter(new LineFormatter(null, null, true, true));
		$logger->pushHandler($handler);

		static::$logger = $logger;
	}

	public static function __callStatic($name, $arguments)
	{
		if (array_key_exists($name, static::$levels)) {
			if (static::$logger === null) {
				static::init();
			}

			static::addEntry(static::$levels[$name], $arguments[0]);
			return;
		}

		$trace = debug_backtrace();
		trigger_error(
			'Undefined method via __callStatic(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_ERROR);
	}

	protected static function config() { }

	protected static function addEntry($level, $entry)
	{
		return static::$logger->addRecord($level, $entry);
	}

}