<?php
namespace Craft;

/**
 * Class ImageHelper
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
			$targetHeight = ceil($targetWidth / $factor);
		}
		else if (empty($targetWidth))
		{
			$targetWidth = ceil($targetHeight * $factor);
		}

		return array($targetWidth, $targetHeight);
	}

	/**
	 * Returns whether an image extension is considered manipulatable.
	 *
	 * @param $extension
	 *
	 * @return array
	 */
	public static function isImageManipulatable($extension)
	{
		$path = craft()->path->getResourcesPath();
		$file = $path."images/samples/sample.".StringHelper::toLowerCase($extension);

		try
		{
			craft()->images->loadImage($file);
			return true;
		}
		catch(\Exception $e)
		{
			return false;
		}
	}

	/**
	 * Returns a list of web safe image formats.
	 *
	 * @return array
	 */
	public static function getWebSafeFormats()
	{
		return array('jpg', 'jpeg', 'gif', 'png', 'svg');
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
	 * Returns whether an image can have EXIF information embedded.
	 *
	 * @param string $filePath The path to the image
	 *
	 * @return bool
	 */
	public static function canHaveExifData($filePath)
	{
		$extension = IOHelper::getExtension($filePath);

		return in_array(StringHelper::toLowerCase($extension), array('jpg', 'jpeg', 'tiff'));
	}

	/**
	 * Returns the size of an image based on its file path.
	 *
	 * @param string $filePath The path to the image
	 *
	 * @return array [$width, $height]
	 */
	public static function getImageSize($filePath)
	{
		if (IOHelper::getExtension($filePath) == 'svg')
		{
			$svg = IOHelper::getFileContents($filePath);
			return static::parseSvgSize($svg);
		}
		else
		{
			$image = craft()->images->loadImage($filePath);
			return array($image->getWidth(), $image->getHeight());
		}
	}

	/**
	 * Parses SVG data and determines its size (normalized to pixels).
	 *
	 * @param string $svg The SVG data
	 *
	 * @return array [$width, $height]
	 */
	public static function parseSvgSize($svg)
	{
		if (
			preg_match(SvgImage::SVG_WIDTH_RE, $svg, $widthMatch) &&
			preg_match(SvgImage::SVG_HEIGHT_RE, $svg, $heightMatch) &&
			($matchedWidth = floatval($widthMatch[2])) &&
			($matchedHeight = floatval($heightMatch[2]))
		)
		{
			$width = round($matchedWidth * self::_getSizeUnitMultiplier($widthMatch[3]));
			$height = round($matchedHeight * self::_getSizeUnitMultiplier($heightMatch[3]));
		}
		elseif (preg_match(SvgImage::SVG_VIEWBOX_RE, $svg, $viewboxMatch))
		{
			$width = round($viewboxMatch[3]);
			$height = round($viewboxMatch[4]);
		}
		else
		{
			$width = null;
			$height = null;
		}

		return array($width, $height);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the multiplier that should be used to convert an image size unit to pixels.
	 *
	 * @param string $unit
	 *
	 * @return float The multiplier
	 */
	private static function _getSizeUnitMultiplier($unit)
	{
		$ppi = 72;

		switch ($unit)
		{
			case 'px':
			{
				return 1;
			}
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
			default:
			{
				return 1;
			}
		}
	}
}
