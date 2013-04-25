<?php
namespace Craft;

/**
 *
 */
class FileLogRoute extends \CFileLogRoute
{
	/**
	 *
	 */
	public function init()
	{
		$this->setLogPath(craft()->path->getLogPath());
		$this->setLogFile('craft.log');

		$this->levels = craft()->config->get('devMode') ? '' : 'error,warning';
		$this->filter = craft()->config->get('devMode') ? 'CLogFilter' : null;

		parent::init();
	}

	/**
	 * Saves log messages in files.
	 *
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{
		$logFile = IOHelper::normalizePathSeparators($this->getLogPath().'/'.$this->getLogFile());

		if (IOHelper::getFileSize($logFile) > $this->getMaxFileSize() * 1024)
		{
			$this->rotateFiles();
		}

		$lock = craft()->config->get('useLockWhenWritingToFile') === true;

		$fp = @fopen($logFile, 'a');

		if ($lock)
		{
			@flock($fp, LOCK_EX);
		}

		foreach ($logs as $log)
		{
			$message = LoggingHelper::redact($log[0]);
			$level = $log[1];
			$category = $log[2];
			$time = $log[3];
			$force = (isset($log[4]) && $log[4] == true) ? true : false;

			@fwrite($fp, $this->formatLogMessage($message, $level, $category, $time, $force));
		}

		@fwrite($fp, PHP_EOL.'******************************************************************************************************'.PHP_EOL);

		if ($lock)
		{
			@flock($fp, LOCK_UN);
		}

		@fclose($fp);
	}

	/**
	 * Formats a log message given different fields.
	 *
	 * @param  string  $message  The message content
	 * @param  integer $level    The message level
	 * @param  string  $category The message category
	 * @param  integer $time     The message timestamp
	 * @param  bool    $force    Whether the message was forced or not
	 *
	 * @return string            formatted message
	 */
	protected function formatLogMessage($message, $level, $category, $time, $force)
	{
		return @date('Y/m/d H:i:s',$time)." [$level] [$category]".($force ? " [Forced]" : "")." $message\n";
	}
}
