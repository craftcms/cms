<?php
namespace Craft;

/**
 *
 */
class LoggingHelper
{
	/**
	 * @static
	 * @param $level
	 * @param $time
	 * @param $category
	 * @param $content
	 * @param null $groupName
	 */
	public static function processFireBugLogEntry($level, $time, $category, $content, $groupName = null)
	{
		$time = date('H:i:s.', $time).sprintf('%03d', (int)(($time - (int)$time) * 1000));

		if ($level === \CLogger::LEVEL_WARNING)
		{
			$func = 'warn';
		}
		else if ($level === \CLogger::LEVEL_ERROR)
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

		$content = \CJavaScript::quote("[$time][$level][$category]\n$content");
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
	 * @return mixed
	 */
	public static function redact($log)
	{
		// Will match 'password => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'password' => (')(.*)('),/uim", "'password' => REDACTED,", $log);

		// Will match 'newPassword => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'newPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

		return $log;
	}
}
