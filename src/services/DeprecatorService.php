<?php
namespace Craft;

/**
 *
 */
class DeprecatorService extends BaseApplicationComponent
{
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
		$stackTrace = JsonHelper::encode($stackInfo['stackTrack']);

		$deprecatedLog = new DeprecationLogRecord();
		$deprecatedLog->key = $key;
		$deprecatedLog->fingerprint = $stackInfo['fingerPrint'];
		$deprecatedLog->message = $message;
		$deprecatedLog->deprecatedSince = $deprecatedSince;
		$deprecatedLog->file = $file;
		$deprecatedLog->line = $line;
		$deprecatedLog->method = $method;
		$deprecatedLog->class = $class;
		$deprecatedLog->stackTrace = $stackTrace;

		$deprecatedLog->save();
	}

	/**
	 * @param $stackTrace
	 * @return array
	 */
	private function _processStackTrace($stackTrace)
	{
		$newStackTrace = array();
		$class = false;
		$template = false;

		$fingerPrint = isset($stackTrace[1]) && isset($stackTrace[1]['class']) ? $stackTrace[1]['class'] : '';

		foreach ($stackTrace as $data)
		{
			$newData = array();
			$newData['method'] = isset($data['function']) ? $data['function'] : '';
			$newData['class'] = isset($data['class']) ? $data['class'] : '';
			$newData['args'] = isset($data['args']) ? $data['args'] : '';

			// Start to build a fingerprint.  If there is a class, use it as the start.
			if (!$class && $fingerPrint)
			{
				$class = true;
			}

			if (isset($data['object']) && is_a($data['object'], '\Twig_Template') && strpos($data['file'], 'compiled_templates') !== false)
			{
				$contents = IOHelper::getFileContents($data['file'], true);
				$newData['template'] = true;

				// The 3rd elements in the array will have the original, un-compiled, template path.
				$filePath = trim($contents[2]);
				$filePath = ltrim($filePath, '/*');
				$filePath = rtrim($filePath, '*/');
				$filePath = trim($filePath);

				$newData['file'] = $filePath;

				// Let's walk backwards from the compiled template line number to find the source line number
				for ($counter = $data['line'] - 1; $counter >= 0; $counter--)
				{
					if (preg_match('/\/\/ line (\d+)$/', trim($contents[$counter]), $matches))
					{
						$newData['line'] = $matches[1];
						break;
					}
				}

				// Overwrite the class fingerprint with the template one.
				if (!$template)
				{
					$fingerPrint = 'template:'.$newData['file'].':'.$newData['line'];
					$template = true;
					$class = false;
				}
			}
			else
			{
				$newData['file'] = isset($data['file']) ? $data['file'] : '';
				$newData['line'] = isset($data['line']) ? $data['line'] : '';
				$newData['template'] = false;

				// Append the line number to our class fingerprint.
				if ($class && $newData['line'] && isset($newData['class']) && $fingerPrint == $newData['class'])
				{
					$fingerPrint .= ':'.$newData['line'];
				}
			}

			$newStackTrace[] = $newData;
		}

		return array('fingerPrint' => $fingerPrint, 'stackTrack' => $newStackTrace);
	}
}
