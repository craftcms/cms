<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use yii\log\Logger;

/**
 * Class LoggingHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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

		if ($level === Logger::LEVEL_WARNING)
		{
			$func = 'warn';
		}
		else if ($level === Logger::LEVEL_ERROR)
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
		// Will match 'password => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'password' => (')(.*)('),/uim", "'password' => REDACTED,", $log);

		// Will match 'newPassword => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'newPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

		// Will match 'smtpPassword => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'smtpPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

		return $log;
	}
}
