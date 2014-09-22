<?php
namespace Craft;

/**
 * Class ImageHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.helpers
 * @since     1.1
 */
class ImageHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Calculates a missing target dimension for an image.
	 *
	 * @param  $targetWidth
	 * @param  $targetHeight
	 * @param  $sourceWidth
	 * @param  $sourceHeight
	 *
	 * @return array Array of the width and height.
	 */
	public static function calculateMissingDimension($targetWidth, $targetHeight, $sourceWidth, $sourceHeight)
	{
		$factor = $sourceWidth / $sourceHeight;

		if (empty($targetHeight))
		{
			$targetHeight = round($targetWidth / $factor);
		}
		else if (empty($targetWidth))
		{
			$targetWidth = round($targetHeight * $factor);
		}

		return array($targetWidth, $targetHeight);
	}

	/**
	 * Returns if an image is manipulatable or not.
	 *
	 * @param $extension
	 *
	 * @return array
	 */
	public static function isImageManipulatable($extension)
	{
		return in_array(mb_strtolower($extension), array('jpg', 'jpeg', 'gif', 'png', 'wbmp', 'xbm'));

	}

	/**
	 * Return a list of web safe formats.
	 * 
	 * @return array
	 */
	public static function getWebSafeFormats()
	{
		return array('jpg', 'jpeg', 'gif', 'png');
	}
}
