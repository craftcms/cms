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
	 * @return array
	 */
	protected function extractMessages($fileName, $translator)
	{
		echo "Extracting messages from $fileName...\n";

		$subject = IOHelper::getFileContents($fileName);
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

	/**
	 * @param $messages
	 * @param $fileName
	 * @param $overwrite
	 * @param $removeOld
	 * @param $sort
	 */
	protected function generateMessageFile($messages, $fileName, $overwrite, $removeOld, $sort)
	{
		echo "Saving messages to $fileName...";

		if (IOHelper::fileExists($fileName))
		{
			$translated = require($fileName);
			sort($messages);
			ksort($translated);

			if (array_keys($translated) == $messages)
			{
				echo "nothing new...skipped.\n";
				return;
			}

			$merged = array();
			$untranslated = array();

			foreach ($messages as $message)
			{
				if (!empty($translated[$message]))
					$merged[$message] = $translated[$message];
				else
					$untranslated[] = $message;
			}

			ksort($merged);
			sort($untranslated);

			$todo = array();
			foreach ($untranslated as $message)
				$todo[$message] = '';

			ksort($translated);

			foreach ($translated as $message => $translation)
			{
				if (!isset($merged[$message]) && !isset($todo[$message]) && !$removeOld)
				{
					if (substr($translation, 0, 2) === '@@' && substr($translation, -2) === '@@')
						$todo[$message] = $translation;
					else
						$todo[$message] = '@@'.$translation.'@@';
				}
			}

			$merged = array_merge($todo, $merged);

			if ($sort)
				ksort($merged);

			if ($overwrite === false)
				$fileName .= '.merged';

			echo "translation merged.\n";
		}
		else
		{
			$merged = array();
			foreach ($messages as $message)
				$merged[$message] = '';

			ksort($merged);
			echo "saved.\n";
		}

		$array = str_replace("\r", '', var_export($merged, true));
		$content=<<<EOD
<?php

return $array;

EOD;
		file_put_contents($fileName, $content);
	}
}
