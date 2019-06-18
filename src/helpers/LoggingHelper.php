<?php
namespace Craft;

/**
 * Class LoggingHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
 */
class LoggingHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * @param      $level
	 * @param      $time
	 * @param      $category
	 * @param      $content
	 * @param null $groupName
	 * @param bool $forced
	 *
	 * @return null
	 */
	public static function processFireBugLogEntry($level, $time, $category, $content, $groupName = null, $forced = false)
	{
		$time = date('H:i:s.', $time).sprintf('%03d', (int)(($time - (int)$time) * 1000));

		if ($level === LogLevel::Warning)
		{
			$func = 'warn';
		}
		else if ($level === LogLevel::Error)
		{
			$func = 'error';
		}
		else
		{
			$func = 'log';
		}

		if ($groupName !== null)
		{
			echo "\tconsole.groupCollapsed(\"{$groupName}\");\n";
		}

		$content = \CJavaScript::quote("[$time][$level][$category]".($forced ? "[Forced]" : "")."\n$content");
		echo "\tconsole.{$func}(\"{$content}\");\n";

		if ($groupName !== null)
		{
			echo "\tconsole.groupEnd();\n";
		}
	}

	/**
	 * Will attempt to remove passwords from the log file.
	 *
	 * @param $log
	 *
	 * @return string
	 */
	public static function redact($log)
	{
		foreach (array('key', 'pass', 'password', 'pw', 'secret', 'tok', 'token',) as $keyword)
		{
			$log = preg_replace("/'(.*)({$keyword})(.*)' => '(.*)'/uim", "'$1$2$3' => REDACTED", $log);
		}

		return $log;
	}
}
