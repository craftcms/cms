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
			$n = preg_match_all('/\b'.$translator.'\s*\(\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*,\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s', $subject, $matches, PREG_SET_ORDER);

			for ($i = 0; $i < $n; ++$i)
			{
				if (($pos = strpos($matches[$i][1], '.')) !== false)
					$category = substr($matches[$i][1], $pos + 1, -1);
				else
					$category = substr($matches[$i][1], 1, -1);

				$message = $matches[$i][2];
				$messages[$category][] = eval("return $message;");  // use eval to eliminate quote escape
			}
		}

		return $messages;
	}
}
