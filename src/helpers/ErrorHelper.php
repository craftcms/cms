<?php
namespace Craft;

/**
 *
 */
class ErrorHelper
{
	/**
	 * Renders the source code around the error line.
	 *
	 * @param string $file source file path
	 * @param integer $errorLine the error line number
	 * @param integer $maxLines maximum number of lines to display
	 * @return string the rendering result
	 */
	public static function renderSourceCode($file, $errorLine, $maxLines)
	{
		$errorLine--;	// adjust line number to 0-based from 1-based

		if ($errorLine < 0 || ($lines = @file($file)) === false || ($lineCount = count($lines)) <= $errorLine)
		{
			return '';
		}

		$halfLines = (int)($maxLines / 2);
		$beginLine = $errorLine - $halfLines > 0 ? $errorLine - $halfLines : 0;
		$endLine = $errorLine + $halfLines < $lineCount ? $errorLine + $halfLines : $lineCount - 1;
		$lineNumberWidth = mb_strlen($endLine + 1);

		$output='';
		for ($i = $beginLine; $i <= $endLine; ++$i)
		{
			$isErrorLine = $i === $errorLine;
			$code = sprintf("<span class=\"ln".($isErrorLine ? ' error-ln' : '')."\">%0{$lineNumberWidth}d</span> %s", $i + 1, HtmlHelper::encode(str_replace("\t", '    ', $lines[$i])));

			if (!$isErrorLine)
			{
				$output .= $code;
			}
			else
			{
				$output .= '<span class="error">'.$code.'</span>';
			}
		}

		return '<div class="code"><pre>'.$output.'</pre></div>';
	}

	/**
	 * Returns a value indicating whether the call stack is from application code.
	 *
	 * @param array $trace the trace data
	 * @return boolean whether the call stack is from application code.
	 */
	public static function isCoreCode($trace)
	{
		if (isset($trace['file']))
		{
			$systemPath = realpath(dirname(__FILE__).'/..');
			return $trace['file'] === 'unknown' || mb_strpos(realpath($trace['file']), $systemPath.DIRECTORY_SEPARATOR) === 0;
		}

		return false;
	}

	/**
	 * Converts arguments array to its string representation
	 *
	 * @param array $args arguments array to be converted
	 * @return string string representation of the arguments array
	 */
	public static function argumentsToString($args)
	{
		$count = 0;
		$isAssoc = $args !== array_values($args);

		foreach ($args as $key => $value)
		{
			$count++;

			if ($count >= 5)
			{
				if ($count > 5)
				{
					unset($args[$key]);
				}
				else
				{
					$args[$key] = '...';
				}

				continue;
			}

			if (is_object($value))
			{
				$args[$key] = get_class($value);
			}
			else if (is_bool($value))
			{
				$args[$key] = $value ? 'true' : 'false';
			}
			else if (is_string($value))
			{
				if (mb_strlen($value) > 64)
				{
					$args[$key] = '"'.mb_substr($value, 0, 64).'..."';
				}
				else
				{
					$args[$key] = '"'.$value.'"';
				}
			}
			else if (is_array($value))
			{
				$args[$key] = 'array('.static::argumentsToString($value).')';
			}
			else if ($value === null)
			{
				$args[$key] = 'null';
			}
			else if (is_resource($value))
			{
				$args[$key] = 'resource';
			}

			if (is_string($key))
			{
				$args[$key] = '"'.$key.'" => '.$args[$key];
			}
			else if ($isAssoc)
			{
				$args[$key] = $key.' => '.$args[$key];
			}
		}

		$out = implode(", ", $args);

		return $out;
	}
}
