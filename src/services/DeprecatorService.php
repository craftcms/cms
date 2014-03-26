<?php
namespace Craft;

/**
 *
 */
class DeprecatorService extends BaseApplicationComponent
{
	private $_fingerprints = array();
	private $_allLogs;

	private static $_tableName = 'deprecationerrors';

	/**
	 * Logs a new deprecation error.
	 *
	 * @param $key
	 * @param $message
	 * @return bool
	 */
	public function log($key, $message)
	{
		$log = new DeprecationErrorModel();

		$log->key            = $key;
		$log->message        = $message;
		$log->lastOccurrence = DateTimeHelper::currentTimeForDb();
		$log->template       = craft()->templates->getRenderingTemplate();

		// Everything else requires the stack trace
		$this->_populateLogWithStackTraceData($log);

		// Don't log the same key/fingerprint twice in the same request
		if (!isset($this->_fingerprints[$log->key]) || !in_array($log->fingerprint, $this->_fingerprints[$log->key]))
		{
			craft()->db->createCommand()->insertOrUpdate(static::$_tableName, array(
				'key'            => $log->key,
				'fingerprint'    => $log->fingerprint
			), array(
				'lastOccurrence' => $log->lastOccurrence,
				'file'           => $log->file,
				'line'           => $log->line,
				'class'          => $log->class,
				'method'         => $log->method,
				'template'       => $log->template,
				'templateLine'   => $log->templateLine,
				'message'        => $log->message,
				'traces'         => JsonHelper::encode($log->traces),
			));

			$this->_fingerprints[$key][] = $log->fingerprint;
		}

		return true;
	}

	/**
	 * Returns the total number of deprecation errors that have been logged.
	 *
	 * @return int
	 */
	public function getTotalLogs()
	{
		return craft()->db->createCommand()
			->from(static::$_tableName)
			->count('id');
	}

	/**
	 * Get 'em all.
	 *
	 * @param int $limit
	 * @return array
	 */
	public function getLogs($limit = 100)
	{
		if (!isset($this->_allLogs))
		{
			$result = craft()->db->createCommand()
				->select('*')
				->from(static::$_tableName)
				->limit($limit)
				->order('lastOccurrence desc')
				->queryAll();

			$this->_allLogs = DeprecationErrorModel::populateModels($result);
		}

		return $this->_allLogs;
	}

	/**
	 * Returns a log by its ID.
	 *
	 * @param $logId
	 * @return DeprecationErrorModel|null
	 */
	public function getLogById($logId)
	{
		$log = craft()->db->createCommand()
			->select('*')
			->from(static::$_tableName)
			->where('id = :logId', array(':logId' => $logId))
			->queryRow();

		if ($log)
		{
			return new DeprecationErrorModel($log);
		}
	}

	/**
	 * Deletes a log by its ID.
	 *
	 * @param $id
	 * @return bool
	 */
	public function deleteLogById($id)
	{
		$affectedRows = craft()->db->createCommand()->delete(static::$_tableName, array('id' => $id));
		return (bool) $affectedRows;
	}

	/**
	 * Populates a DeprecationErrorModel with data pulled from the PHP stack trace.
	 *
	 * @access private
	 * @param DeprecationErrorModel $log
	 */
	private function _populateLogWithStackTraceData(DeprecationErrorModel $log)
	{
		$traces = debug_backtrace();

		// Set the basic stuff
		$log->file   = $traces[0]['file'];
		$log->line   = $traces[0]['line'];
		$log->class  = !empty($traces[1]['class'])    ? $traces[1]['class']    : null;
		$log->method = !empty($traces[1]['function']) ? $traces[1]['function'] : null;

		/* HIDE */
		$foundPlugin = false;
		$pluginsPath = realpath(craft()->path->getPluginsPath()).'/';
		$pluginsPathLength = strlen($pluginsPath);
		/* end HIDE */

		$isTemplateRendering = craft()->templates->isRendering();

		if ($isTemplateRendering)
		{
			// We'll figure out the line number later
			$log->fingerprint = $log->template;

			$foundTemplate = false;
		}
		else
		{
			$log->fingerprint = $log->class.($log->class && $log->line ? ':'.$log->line : '');
		}

		$logTraces = array();

		// Skip the first two, since they're just DeprecatorService stuff
		array_shift($traces);
		array_shift($traces);

		foreach ($traces as $trace)
		{
			$logTrace = array(
				'file'   => (!empty($trace['file'])     ? $trace['file'] : null),
				'line'   => (!empty($trace['line'])     ? $trace['line'] : null),
				'class'  => (!empty($trace['class'])    ? $trace['class'] : null),
				'method' => (!empty($trace['function']) ? $trace['function'] : null),
				'args'   => (!empty($trace['args'])     ? craft()->errorHandler->argumentsToString($trace['args']) : null),
			);

			// Is this a template?
			if (isset($trace['object']) && $trace['object'] instanceof \Twig_Template && 'Twig_Template' !== get_class($trace['object']) && strpos($trace['file'], 'compiled_templates') !== false)
			{
				$template = $trace['object'];

				// Get the original (uncompiled) template name.
				$logTrace['template'] = $template->getTemplateName();

				// Guess the line number
				foreach ($template->getDebugInfo() as $codeLine => $templateLine)
				{
					if ($codeLine <= $trace['line'])
					{
						$logTrace['templateLine'] = $templateLine;

						// Save that to the main log info too?
						if ($isTemplateRendering && !$foundTemplate)
						{
							$log->templateLine = $templateLine;
							$log->fingerprint .= ':'.$templateLine;
							$foundTemplate = true;
						}

						break;
					}
				}

				/* HIDE */
				if ($isTemplateRendering && !$foundTemplate)
				{
					// Is this a plugin's template?
					if (!$foundPlugin && craft()->request->isCpRequest() && $logTrace['template'])
					{
						$firstSeg = array_shift(explode('/', $logTrace['template']));

						if (craft()->plugins->getPlugin($firstSeg))
						{
							$log->plugin = $firstSeg;
							$foundPlugin = true;
						}
					}

					$foundTemplate = true;
				}
				/* end HIDE */
			}

			/* HIDE */
			// Is this a plugin's file?
			else if (!$foundPlugin && $logTrace['file'])
			{
				$filePath = realpath($logTrace['file']).'/';

				if (strncmp($pluginsPath, $logTrace['file'], $pluginsPathLength) === 0)
				{
					$remainingFilePath = substr($filePath, $pluginsPathLength);
					$firstSeg = array_shift(explode('/', $remainingFilePath));

					if (craft()->plugins->getPlugin($firstSeg))
					{
						$log->plugin = $firstSeg;
						$foundPlugin = true;
					}
				}
			}
			/* end HIDE */

			$logTraces[] = $logTrace;
		}

		$log->traces = $logTraces;
	}
}
