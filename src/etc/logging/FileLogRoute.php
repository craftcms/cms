<?php
namespace Craft;

/**
 * Class FileLogRoute
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.logging
 * @since     1.0
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
		$this->setLogPath(craft()->path->getLogPath());

		$this->levels = craft()->config->get('devMode') ? '' : 'error,warning';
		$this->filter = craft()->config->get('devMode') ? 'Craft\\LogFilter' : null;

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
			if (craft()->config->get('useWriteFileLock') === true)
			{
				$lock = true;
			}
			// Is it set to false?
			else if (craft()->config->get('useWriteFileLock') === false)
			{
				$lock = false;
			}
			// Config setting it set to 'auto', so check cache.
			else if (craft()->cache->get('useWriteFileLock') === 'yes')
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
