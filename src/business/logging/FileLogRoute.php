<?php
namespace Blocks;

/**
 *
 */
class FileLogRoute extends \CFileLogRoute
{
	public function init()
	{
		$this->setLogFile('blocks.log');

		$this->levels = b()->config->devMode ? '' : 'error, warning';
		$this->filter = b()->config->devMode ? 'CLogFilter' : null;

		parent::init();
	}

	/**
	 * Saves log messages in files.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{
		$logFile = $this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();

		if (@filesize($logFile) > $this->getMaxFileSize() * 1024)
			$this->rotateFiles();

		$fp = @fopen($logFile, 'a');
		@flock($fp, LOCK_EX);

		foreach ($logs as $log)
			@fwrite($fp, $this->formatLogMessage($log[0], $log[1], $log[2], $log[3]));

		@fwrite($fp, PHP_EOL.'******************************************************************************************************'.PHP_EOL);

		@flock($fp, LOCK_UN);
		@fclose($fp);
	}
}
