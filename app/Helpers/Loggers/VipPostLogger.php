<?php

namespace App\Helpers\Loggers;

/**
 * Class VipLogger
 *
 * @package App\Helpers\Loggers
 */
class VipPostLogger extends Logger
{
    protected static $postId   = 0;
	protected static $loggerName = 'vipPost';

	protected static function config()
	{
		static::$path = storage_path() . '/logs/vip_posts.log';
	}

    public static function setPostId($postId)
    {
        static::$postId = $postId;
    }


	protected static function addEntry($level, $entry)
	{
        if ( static::$postId > 0) {
            $entry = '[Post ID: ' . static::$postId . '] - ' . $entry;
        }
		parent::addEntry($level, $entry);
	}
}