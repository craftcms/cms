<?php
namespace Craft;

/**
 * Class Svg
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
class SvgImage extends BaseImage
{

	// Constants
	// =========================================================================

	const SVG_WIDTH_RE = '/(<svg[^>]*\swidth=")([\d\.]+)([a-z]*)"/si';
	const SVG_HEIGHT_RE = '/(<svg[^>]*\sheight=")([\d\.]+)([a-z]*)"/si';
	const SVG_VIEWBOX_RE = '/(<svg[^>]*\sviewBox=")(\d+(?:,|\s)\d+(?:,|\s)(\d+)(?:,|\s)(\d+))"/si';
	const SVG_ASPECT_RE = '/(<svg[^>]*\spreserveAspectRatio=")([a-z]+\s[a-z]+)"/si';
	const SVG_TAG_RE = '/<svg/si';

	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_svgContent;

	/**
	 * @var int
	 */
	private $_height;

	/**
	 * @var int
	 */
	private $_width;

	// Public Methods
	// =========================================================================

	/**
	 * @return int
	 */
	public function getWidth()
	{
		return $this->_height;

	}

	/**
	 * @return int
	 */
	public function getHeight()
	{
		return $this->_width;
	}

	/**
	 * @return string
	 */
	public function getExtension()
	{
		return "svg";
	}

	/**
	 * Loads an image from a file system path.
	 *
	 * @param string $path
	 *
	 * @throws Exception
	 * @return Image
	 */
	public function loadImage($path)
	{
		if (!IOHelper::fileExists($path))
		{
			throw new Exception(Craft::t('No file exists at the path “{path}”', array('path' => $path)));
		}

		list($width, $height) = ImageHelper::getImageSize($path);

		$svg = IOHelper::getFileContents($path);

		// If the size is defined by viewbox only, add in width and height attributes
		if (!preg_match(static::SVG_WIDTH_RE, $svg) && preg_match(static::SVG_HEIGHT_RE, $svg))
		{
			$svg = preg_replace(static::SVG_TAG_RE, "<svg width=\"{$width}px\" height=\"{$height}px\" ", $svg);
		}

		$this->_height = $height;
		$this->_width = $width;

		$this->_svgContent = $svg;

		return $this;
	}

	/**
	 * Crops the image to the specified coordinates.
	 *
	 * @param int $x1
	 * @param int $x2
	 * @param int $y1
	 * @param int $y2
	 *
	 * @return Image
	 */
	public function crop($x1, $x2, $y1, $y2)
	{
		$width = $x2 - $x1;
		$height = $y2 - $y1;

		$this->resize($width, $height);

		$value = "{$x1} {$y1} {$width} {$height}";

		// Add/modify the viewbox to crop the image.
		if (preg_match(static::SVG_VIEWBOX_RE, $this->_svgContent))
		{
			$this->_svgContent = preg_replace(static::SVG_VIEWBOX_RE, "\${1}{$value}\"", $this->_svgContent);
		}
		else
		{
			$this->_svgContent = preg_replace(static::SVG_TAG_RE, "<svg viewBox=\"{$value}\"", $this->_svgContent);
		}

		return $this;
	}

	/**
	 * Scale the image to fit within the specified size.
	 *
	 * @param int      $targetWidth
	 * @param int|null $targetHeight
	 * @param bool     $scaleIfSmaller
	 *
	 * @return Image
	 */
	public function scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true)
	{
		$this->normalizeDimensions($targetWidth, $targetHeight);

		if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight)
		{
			$factor = max($this->getWidth() / $targetWidth, $this->getHeight() / $targetHeight);
			$this->resize(round($this->getWidth() / $factor), round($this->getHeight() / $factor));
		}

		return $this;
	}

	/**
	 * Scale and crop image to exactly fit the specified size.
	 *
	 * @param int      $targetWidth
	 * @param int|null $targetHeight
	 * @param bool     $scaleIfSmaller
	 * @param string   $cropPositions
	 *
	 * @return Image
	 */
	public function scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center')
	{
		$this->normalizeDimensions($targetWidth, $targetHeight);

		if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight)
		{
			// Scale first.
			$this->resize($targetWidth, $targetHeight);

			$value = "x". strtr($cropPositions, array(
					'left' => 'Min',
					'center' => 'Mid',
					'right' => 'Max',
					'top' => 'Min',
					'bottom' => 'Max',
					'-' => 'Y'
				)) ." slice";

			// Add/modify aspect ratio information
			if (preg_match(static::SVG_ASPECT_RE, $this->_svgContent))
			{
				$this->_svgContent = preg_replace(static::SVG_ASPECT_RE, "\${1}{$value}\"", $this->_svgContent);
			}
			else
			{
				$this->_svgContent = preg_replace(static::SVG_TAG_RE, "<svg preserveAspectRatio=\"{$value}\"", $this->_svgContent);
			}
		}

		return $this;
	}

	/**
	 * Re-sizes the image. If $height is not specified, it will default to $width, creating a square.
	 *
	 * @param int      $targetWidth
	 * @param int|null $targetHeight
	 *
	 * @return Image
	 */
	public function resize($targetWidth, $targetHeight = null)
	{
		$this->normalizeDimensions($targetWidth, $targetHeight);

		if (preg_match(static::SVG_WIDTH_RE, $this->_svgContent) && preg_match(static::SVG_HEIGHT_RE, $this->_svgContent))
		{
			$this->_svgContent = preg_replace(static::SVG_WIDTH_RE, "\${1}{$targetWidth}px\"", $this->_svgContent);
			$this->_svgContent = preg_replace(static::SVG_HEIGHT_RE, "\${1}{$targetHeight}px\"", $this->_svgContent);
		}
		else
		{
			$this->_svgContent = preg_replace(static::SVG_TAG_RE, "<svg width=\"{$targetWidth}px\" height=\"{$targetHeight}px\"", $this->_svgContent);
		}

		$this->_width = $targetWidth;
		$this->_height = $targetHeight;

		return $this;
	}

	/**
	 * Saves the image to the target path.
	 *
	 * @param string $targetPath
	 *
	 * @throws \Imagine\Exception\RuntimeException
	 * @return null
	 */
	public function saveAs($targetPath, $autoQuality = false)
	{
		IOHelper::writeToFile($targetPath, $this->_svgContent);
		return true;
	}
}
