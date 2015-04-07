<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;
use Craft;

/**
 * Class PathHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PathHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Ensures that a relative path never goes deeper than its root directory.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function ensurePathIsContained($path)
	{
		// Sanitize
		$path = StringHelper::convertToUtf8($path);

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
