<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\helpers\IOHelper;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetManager extends \yii\web\AssetManager
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function publishDirectory($src, $options)
	{
		// See if any of the subdirectories have a more recent modify date than $src
		$srcModTime = filemtime($src);
		$subdirs = glob($src.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT | GLOB_ONLYDIR);

		foreach ($subdirs as $dir)
		{
			if (filemtime($dir) > $srcModTime)
			{
				IOHelper::touch($src, null, true);
				break;
			}
		}

		return parent::publishDirectory($src, $options);
	}
}
