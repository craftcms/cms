<?php
namespace Craft;


class Logger extends \CLogger
{
	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved back via {@link getLogs}.
	 *
	 * @param string $message  The mssage to be logged
	 * @param string $level    The level of the message (e.g. 'Trace', 'Warning', 'Error'). It is case-insensitive.
	 * @param string $category The category of the message (e.g. 'system.web'). It is case-insensitive.
	 * @param bool   $force    Whether for force the message to be logged regardless of category or level
	 */
	public function log($message, $level = 'info', $category = 'application', $force = false)
	{
		$this->_logs[] = array($message, $level, $category, microtime(true));
		$this->_logCount++;

		if ($this->autoFlush > 0 && $this->_logCount >= $this->autoFlush && !$this->_processing)
		{
			$this->_processing = true;
			$this->flush($this->autoDump);
			$this->_processing = false;
		}
	}
}
