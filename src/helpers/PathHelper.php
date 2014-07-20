<?php
namespace Craft;

/**
 * Class PathHelper
 *
 * @package craft.app.helpers
 */
class PathHelper
{
	/**
	 * Ensures that a relative path never goes deeper than its root directory.
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function ensurePathIsContained($path)
	{
		// Sanitize
		$path = craft()->request->decodePathInfo($path);

		$segs = explode('/', $path);
		$level = 0;

		foreach ($segs as $seg)
		{
			if ($seg === '..')
			{
				$level--;
			}
			elseif ($seg !== '.')
			{
				$level++;
			}

			if ($level < 0)
			{
				return false;
			}
		}

		return true;
	}
}
