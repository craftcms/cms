<?php
namespace Craft;

/**
 *
 */
class DeprecatorService extends BaseApplicationComponent
{
	private $_fingerprints = array();
	private $_deprecatorLogs = false;

	public function init()
	{
		parent::init();

		$deprecatorLogs = $this->getAllLogs();

		foreach ($deprecatorLogs as $log)
		{
			$this->_fingerprints[$log->fingerprint] = $log->fingerprint;
		}
	}

	/**
	 * @param $key
	 * @param $message
	 * @param $deprecatedSince
	 */
	public function deprecate($key, $message, $deprecatedSince)
	{
		$stackTrace = debug_backtrace();

		// $stackTrace[0] is (hopefully) guaranteed to be there.
		$caller = $stackTrace[0];

		// The file and line of the caller.
		$file = $caller['file'];
		$line = $caller['line'];

		// For some stupid reason, the method and class of the caller is in the next step of the backtrace.
		$method = isset($stackTrace[1]) && isset($stackTrace[1]['function']) ? $stackTrace[1]['function'] : '';
		$class = isset($stackTrace[1]) && isset($stackTrace[1]['class']) ? $stackTrace[1]['class'] : '';

		$stackInfo = $this->_processStackTrace($stackTrace);

		// If we've already got this fingerprint logged, let's not duplicate it.
		if (!isset($this->_fingerprints[$stackInfo['fingerprint']]))
		{
			$stackTrace = JsonHelper::encode($stackInfo['stackTrack']);

			craft()->db->createCommand()->insert('deprecatorlogs', array(
				'key' => $key,
				'fingerprint' => $stackInfo['fingerprint'],
				'message' => $message,
				'deprecatedSince' => $deprecatedSince,
				'file' => $file,
				'line' => $line,
				'method' => $method,
				'line' => $line,
				'class' => $class,
				'stackTrace' => $stackTrace,
			));

			$this->_fingerprints[$stackInfo['fingerprint']] = $stackInfo['fingerprint'];
		}
	}

	/**
	 * Get 'em all.
	 *
	 * @return array
	 */
	public function getAllLogs()
	{
		if ($this->_deprecatorLogs === false)
		{
			$this->_deprecatorLogs = craft()->db->createCommand()
				->select('*')
				->from('deprecatorlogs')
				->queryAll();
		}

		return DeprecatorLogModel::populateModels($this->_deprecatorLogs);
	}

	/**
	 * Get one log
	 *
	 * @param $logId
	 * @return array
	 */
	public function getLogById($logId)
	{
		$log = craft()->db->createCommand()
			->select('*')
			->from('deprecatorlogs')
			->where('id = :logId', array(':logId' => $logId))
			->queryRow();

		return new DeprecatorLogModel($log);
	}

	/**
	 * @param $id
	 */
	public function deleteLogById($id)
	{
		craft()->db->createCommand()->delete('deprecatorlogs', array('id' => $id));
	}

	/**
	 * @param $stackTrace
	 * @return array
	 */
	private function _processStackTrace($stackTrace)
	{
		$newStackTrace = array();
		$foundClass = false;
		$foundTemplate = false;

		$fingerprint = isset($stackTrace[1]) && isset($stackTrace[1]['class']) ? $stackTrace[1]['class'] : '';

		foreach ($stackTrace as $data)
		{
			$newData = array();
			$newData['method'] = isset($data['function']) ? $data['function'] : '';
			$newData['class'] = isset($data['class']) ? $data['class'] : '';
			$newData['args'] = isset($data['args']) ? $data['args'] : '';

			// Start to build a fingerprint.  If there is a class, use it as the start.
			if (!$foundClass && $fingerprint)
			{
				$foundClass = true;
			}

			if (isset($data['object']) && $data['object'] instanceof \Twig_Template && 'Twig_Template' !== get_class($data['object']) && strpos($data['file'], 'compiled_templates') !== false)
			{
				$newData['template'] = true;
				$template = $data['object'];

				// Get the original (uncompiled) template name.
				$newData['file'] = $template->getTemplateName();

				foreach ($template->getDebugInfo() as $codeLine => $templateLine)
				{
					if ($codeLine <= $data['line'])
					{
						// Grab the original source line.
						$newData['line'] = $templateLine;
						break;
					}
				}

				// Overwrite the class fingerprint with the template one.
				if (!$foundTemplate)
				{
					$fingerprint = 'template:'.$newData['file'].':'.$newData['line'];
					$foundTemplate = true;
					$foundClass = false;
				}
			}
			else
			{
				$newData['file'] = isset($data['file']) ? $data['file'] : '';
				$newData['line'] = isset($data['line']) ? $data['line'] : '';
				$newData['template'] = false;

				// Append the line number to our class fingerprint.
				if ($foundClass && $newData['line'] && isset($newData['class']) && $fingerprint == $newData['class'])
				{
					$fingerprint .= ':'.$newData['line'];
				}
			}

			$newStackTrace[] = $newData;
		}

		return array('fingerprint' => $fingerprint, 'stackTrack' => $newStackTrace);
	}
}
