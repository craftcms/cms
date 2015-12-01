<?php
namespace Craft;

/**
 * Base Image class
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.image
 * @since     2.5
 */
abstract class BaseImage
{

	/**
	 * @return int
	 */
	abstract public function getWidth();

	/**
	 * @return int
	 */
	abstract public function getHeight();

	/**
	 * @return string
	 */
	abstract public function getExtension();

	/**
	 * Loads an image from a file system path.
	 *
	 * @param string $path
	 *
	 * @throws Exception
	 * @return BaseImage
	 */
	abstract public function loadImage($path);

	/**
	 * Crops the image to the specified coordinates.
	 *
	 * @param int $x1
	 * @param int $x2
	 * @param int $y1
	 * @param int $y2
	 *
	 * @return BaseImage
	 */
	abstract public function crop($x1, $x2, $y1, $y2);

	/**
	 * Scale the image to fit within the specified size.
	 *
	 * @param int      $targetWidth
	 * @param int|null $targetHeight
	 * @param bool     $scaleIfSmaller
	 *
	 * @return BaseImage
	 */
	abstract public function scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true);

	/**
	 * Scale and crop image to exactly fit the specified size.
	 *
	 * @param int      $targetWidth
	 * @param int|null $targetHeight
	 * @param bool     $scaleIfSmaller
	 * @param string   $cropPositions
	 *
	 * @return BaseImage
	 */
	abstract public function scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center');

	/**
	 * Re-sizes the image. If $height is not specified, it will default to $width, creating a square.
	 *
	 * @param int      $targetWidth
	 * @param int|null $targetHeight
	 *
	 * @return Image
	 */
	abstract public function resize($targetWidth, $targetHeight = null);

	/**
	 * Saves the image to the target path.
	 *
	 * @param string $targetPath
	 *
	 * @return null
	 */
	abstract public function saveAs($targetPath, $autoQuality = false);

	/**
	 * Returns true if the image is transparent.
	 *
	 * @return bool
	 */
	abstract public function isTransparent();

	// Protected Methods
	// =========================================================================

	/**
	 * Normalizes the given dimensions.  If width or height is set to 'AUTO', we calculate the missing dimension.
	 *
	 * @param int|string $width
	 * @param int|string $height
	 *
	 * @throws Exception
	 */
	protected function normalizeDimensions(&$width, &$height = null)
	{
		if (preg_match('/^(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)/', $width, $matches))
		{
			$width  = $matches['width']  != 'AUTO' ? $matches['width']  : null;
			$height = $matches['height'] != 'AUTO' ? $matches['height'] : null;
		}

		if (!$height || !$width)
		{
			list($width, $height) = ImageHelper::calculateMissingDimension($width, $height, $this->getWidth(), $this->getHeight());
		}
	}
}
