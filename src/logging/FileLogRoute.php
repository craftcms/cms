<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\logging;

use craft\app\Craft;
use craft\app\helpers\IOHelper;
use craft\app\helpers\LoggingHelper;
use craft\app\helpers\StringHelper;

/**
 * Class FileLogRoute
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileLogRoute extends \CFileLogRoute
{
	// Public Methods
	// =========================================================================

	/**
	 * Initializes the log route.  This method is invoked after the log route is created by the route manager.
	 *
	 * @return null
	 */
	public function init()
	{
		$this->setLogPath(Craft::$app->path->getLogPath());

		$this->levels = Craft::$app->config->get('devMode') ? '' : 'error,warning';
		$this->filter = Craft::$app->config->get('devMode') ? 'Craft\\LogFilter' : null;

		parent::init();
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Saves log messages in files.
	 *
	 * @param array $logs The list of log messages
	 *
	 * @return null
	 */
	protected function processLogs($logs)
	{
		$types = array();

		foreach ($logs as $log)
		{
			$message = LoggingHelper::redact($log[0]);
			$level = $log[1];
			$category = $log[2];
			$time = $log[3];
			$force = (isset($log[4]) && $log[4] == true) ? true : false;
			$plugin = isset($log[5]) ? StringHelper::toLowerCase($log[5]) : 'craft';

			if (isset($types[$plugin]))
			{
				$types[$plugin] .= $this->formatLogMessageWithForce($message, $level, $category, $time, $force, $plugin);
			}
			else
			{
				$types[$plugin] = $this->formatLogMessageWithForce($message, $level, $category, $time, $force, $plugin);
			}
		}

		foreach ($types as $plugin => $text)
		{
			$text .= PHP_EOL.'******************************************************************************************************'.PHP_EOL;

			$this->setLogFile($plugin.'.log');

			$logFile = IOHelper::normalizePathSeparators($this->getLogPath().'/'.$this->getLogFile());

			// Check the config setting first.  Is it set to true?
			if (Craft::$app->config->get('useWriteFileLock') === true)
			{
				$lock = true;
			}
			// Is it set to false?
			else if (Craft::$app->config->get('useWriteFileLock') === false)
			{
				$lock = false;
			}
			// Config setting it set to 'auto', so check cache.
			else if (Craft::$app->cache->get('useWriteFileLock') === 'yes')
			{
				$lock = true;
			}
			else
			{
				$lock = false;
			}

			$fp = @fopen($logFile, 'a');

			if ($lock)
			{
				@flock($fp, LOCK_EX);
			}

			if (IOHelper::getFileSize($logFile) > $this->getMaxFileSize() * 1024)
			{
				$this->rotateFiles();

				if ($lock)
				{
					@flock($fp, LOCK_UN);
				}

				@fclose($fp);

				if ($lock)
				{
					IOHelper::writeToFile($logFile, $text, false, true, false);
				}
				else
				{
					IOHelper::writeToFile($logFile, $text, false, true, true);
				}
			}
			else
			{
				@fwrite($fp, $text);

				if ($lock)
				{
					@flock($fp, LOCK_UN);
				}

				@fclose($fp);
			}
		}
	}

	/**
	 * Formats a log message given different fields.
	 *
	 * @param string $message  The message content.
	 * @param int    $level    The message level.
	 * @param string $category The message category.
	 * @param int    $time     The message timestamp.
	 * @param  bool  $force    Whether the message was forced or not.
	 *
	 * @return string The formatted message.
	 */
	protected function formatLogMessageWithForce($message, $level, $category, $time, $force)
	{
		return @date('Y/m/d H:i:s',$time)." [$level] [$category]".($force ? " [Forced]" : "")." $message\n";
	}
}
