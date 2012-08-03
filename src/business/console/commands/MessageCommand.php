<?php
namespace Blocks;

/**
 *
 */
class MessageCommand extends \MessageCommand
{
	/**
	 * @param $fileName
	 * @param $translator
	 *
	 * @return array
	 */
	protected function extractMessages($fileName, $translator)
	{
		echo "Extracting messages from $fileName...\n";

		$subject = file_get_contents($fileName);
		$messages = array();

		$translators = explode(',', $translator);

		foreach ($translators as $translator)
		{
			$translator = str_replace('.', '\.', $translator);
			$enumMatch = false;

			// first check with the category name in quote
			$n = preg_match_all('/\b'.$translator.'\s*\(\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*,\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s', $subject, $matches, PREG_SET_ORDER);

			if ($n == 0)
			{
				// now check for the category as an enum
				$n = preg_match_all('/\b'.$translator.'\s*\(\s*(.*?(?<!\\\\).*?(?<!\\\\))\s*,\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s', $subject, $matches, PREG_SET_ORDER);
				if ($n > 0)
					$enumMatch = true;
			}

			for ($i = 0; $i < $n; ++$i)
			{
				if ($enumMatch)
				{
					$segs = explode('::', $matches[$i][1]);
					$category = $segs[1];
				}
				else
				{
					if (($pos = strpos($matches[$i][1], '.')) !== false)
						$category = substr($matches[$i][1], $pos + 1, -1);
					else
						$category = substr($matches[$i][1], 1, -1);
				}

				$message = $matches[$i][2];
				$messages[$category][] = eval("return $message;");  // use eval to eliminate quote escape
			}
		}

		return $messages;
	}
}
