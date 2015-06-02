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
	// Constants
	// =========================================================================

	const EXIF_IFD0_ROTATE_180 = 3;
	const EXIF_IFD0_ROTATE_90  = 6;
	const EXIF_IFD0_ROTATE_270 = 8;

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
		$extensions = array('jpg', 'jpeg', 'gif', 'png', 'wbmp', 'xbm');

		if (craft()->images->isImagick())
		{
			$extensions[] = 'svg';
		}

		return in_array(mb_strtolower($extension), $extensions);

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
	 * Returns any info that’s embedded in a given PNG file.
	 *
	 * Adapted from https://github.com/ktomk/Miscellaneous/tree/master/get_png_imageinfo.
	 *
	 * @param string $file The path to the PNG file.
	 *
	 * @author Tom Klingenberg <lastflood.net>
	 * @license Apache 2.0
	 * @version 0.1.0
	 * @link http://www.libpng.org/pub/png/spec/iso/index-object.html#11IHDR
	 *
	 * @return array|bool Info embedded in the PNG file, or `false` if it wasn’t found.
	 */
	public static function getPngImageInfo($file)
	{
		if (empty($file))
		{
			return false;
		}

		$info = unpack(
			'A8sig/Nchunksize/A4chunktype/Nwidth/Nheight/Cbit-depth/Ccolor/Ccompression/Cfilter/Cinterface',
			file_get_contents($file, 0, null, 0, 29)
		);

		if (!$info)
		{
			return false;
		}

		$sig = array_shift($info);

		if ($sig != "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A" && $sig != "\x89\x50\x4E\x47\x0D\x0A\x1A")
		{
			// The file doesn't have a PNG signature
			return false;
		}

		if (array_shift($info) != 13)
		{
			// The IHDR chunk has the wrong length
			return false;
		}

		if (array_shift($info) !== 'IHDR')
		{
			// A non-IHDR chunk singals invalid data
			return false;
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
			// Invalid color value
			return false;
		}

		$info['color-type'] = $type[$color];
		$samples = ((($color % 4) % 3) ? 3 : 1) + ($color > 3);
		$info['channels'] = $samples;

		return $info;
	}

	/**
	 * Return true if the file can have EXIF information embedded.
	 *
	 * @param string $filePath the file path to check.
	 *
	 * @return bool
	 */
	public static function canHaveExifData($filePath)
	{
		$extension = IOHelper::getExtension($filePath);

		return in_array(StringHelper::toLowerCase($extension), array('jpg', 'jpeg', 'tiff'));
	}

	/**
	 * Return an image size for a file path
	 *
	 * @param $filePath
	 *
	 * @return array [$width, $height]
	 */
	public static function getImageSize($filePath)
	{
		if (IOHelper::getExtension($filePath) == 'svg')
		{
			return static::getSvgSize($filePath);
		}
		else
		{
			$image = craft()->images->loadImage($filePath);
			return array($image->getWidth(), $image->getHeight());
		}
	}

	/**
	 * Get dimensions for an SVG file by path
	 *
	 * @param $filePath
	 *
	 * @return array [$width, $height]
	 */
	public static function getSvgSize($filePath)
	{
		$svgData = file_get_contents($filePath);

		$widthRegex = '/.*<svg[^>]* width="([\d]+)([a-z]*)"/si';
		$heightRegex = '/.*<svg[^>]* height="([\d]+)([a-z]*)"/si';

		if (
			preg_match($widthRegex, $svgData, $widthMatch) &&
			preg_match($heightRegex, $svgData, $heightMatch) &&
			($matchedWidth = floatval($widthMatch[1])) &&
			($matchedHeight = floatval($heightMatch[1]))
		)
		{
			$widthUnits = $widthMatch[2];
			$heightUnits = $heightMatch[2];

			$getMultiplier = function ($unit)
			{
				$ppi = 72;

				switch ($unit)
				{
					case 'in':
					{
						return $ppi;
					}
					case 'pt':
					{
						return $ppi / 72;
					}
					case 'pc':
					{
						return $ppi / 6;
					}
					case 'cm':
					{
						return $ppi / 2.54;
					}
					case 'mm':
					{
						return $ppi / 25.4;
					}
					case 'em':
					{
						return 16;
					}
					case 'ex':
					{
						return 10;
					}

					case 'px':
					default:
						{
						return 1;
						}
				}
			};

			return array($matchedWidth * $getMultiplier($widthUnits), $matchedHeight * $getMultiplier($heightUnits));
		}
		else
		{
			return array(null, null);
		}
	}
}
