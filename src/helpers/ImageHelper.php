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

	/**
	 * Get image-information from PNG file.
	 * Adapted from https://github.com/ktomk/Miscellaneous/tree/master/get_png_imageinfo
	 *
	 * @author Tom Klingenberg <lastflood.net>
	 * @license Apache 2.0
	 * @version 0.1.0
	 * @link http://www.libpng.org/pub/png/spec/iso/index-object.html#11IHDR
	 *
	 * @param string $file filename
	 *
	 * @return array|bool image information, FALSE on error
	 */
	public static function getPngImageInfo($file) {

		if (empty($file))
		{
			return false;
		}

		$info = unpack(
			'A8sig/Nchunksize/A4chunktype/Nwidth/Nheight/Cbit-depth/'.
			'Ccolor/Ccompression/Cfilter/Cinterface',
			file_get_contents($file,0,null,0,29));

		if (empty($info))
		{
			return false;
		}

		if ("\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" != array_shift($info))
		{
			return false; // no PNG signature.
		}

		if (13 != array_shift($info))
		{
			return false; // wrong length for IHDR chunk.
		}

		if ('IHDR'!==array_shift($info))
		{
			return false; // a non-IHDR chunk singals invalid data.
		}

		$color = $info['color'];
		$type = array(
			0 => 'Greyscale',
			2 => 'Truecolour',
			3 => 'Indexed-colour',
			4 => 'Greyscale with alpha',
			6 => 'Truecolor with alpha'
		);

		if (empty($type[$color]))
		{
			return false; // invalid color value
		}

		$info['color-type'] = $type[$color];
		$samples = ((($color % 4) % 3) ? 3 : 1) + ($color > 3);
		$info['channels'] = $samples;
		$info['bits'] = $info['bit-depth'];

		return $info;
	}
}
