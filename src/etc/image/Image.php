<?php
namespace Craft;

/**
 * Class Image
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.image
 * @since     2.5
 */
class Image extends BaseImage
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_imageSourcePath;

	/**
	 * @var string
	 */
	private $_extension;

	/**
	 * @var bool
	 */
	private $_isAnimatedGif = false;

	/**
	 * @var int
	 */
	private $_quality = 0;

	/**
	 * @var \Imagine\Image\ImageInterface
	 */
	private $_image;

	/**
	 * @var \Imagine\Image\ImagineInterface
	 */
	private $_instance;

	/**
	 * @var \Imagine\Image\Palette\RGB
	 */
	private $_palette;

	/**
	 * @var \Imagine\Image\FontInterface
	 */
	private $_font;

	// Public Methods
	// =========================================================================

	/**
	 * @return Image
	 */
	public function __construct()
	{
		if (craft()->images->isGd())
		{
			$this->_instance = new \Imagine\Gd\Imagine();
		}
		else
		{
			$this->_instance = new \Imagine\Imagick\Imagine();
		}

		$this->_quality = craft()->config->get('defaultImageQuality');
	}

	/**
	 * @return int
	 */
	public function getWidth()
	{
		return $this->_image->getSize()->getWidth();

	}

	/**
	 * @return int
	 */
	public function getHeight()
	{
		return $this->_image->getSize()->getHeight();
	}

	/**
	 * @return string
	 */
	public function getExtension()
	{
		return $this->_extension;
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
			Craft::log('Tried to load an image at '.$path.', but the file does not exist.', LogLevel::Error);
			throw new Exception(Craft::t('No file exists at the given path.'));
		}

		if (!craft()->images->checkMemoryForImage($path))
		{
			throw new Exception(Craft::t("Not enough memory available to perform this image operation."));
		}

		// Make sure the image says it's an image
		$mimeType = FileHelper::getMimeType($path, null, false);

		if ($mimeType !== null && strncmp($mimeType, 'image/', 6) !== 0)
		{
			throw new Exception(Craft::t('The file “{name}” does not appear to be an image.', array('name' => IOHelper::getFileName($path))));
		}

		try
		{
			$this->_image = $this->_instance->open($path);
		}
		catch (\Exception $exception)
		{
			throw new Exception(Craft::t('The file “{name}” does not appear to be an image.', array('name' => IOHelper::getFileName($path))));
		}

		// If we're using Imagick _and_ one that supports it, convert CMYK to RGB, save and re-open.
		if (!craft()->images->isGd() && $this->_image->getImagick()->getImageColorspace() == \Imagick::COLORSPACE_CMYK && method_exists($this->_image->getImagick(), 'transformimagecolorspace'))
		{
			$this->_image->getImagick()->transformimagecolorspace(\Imagick::COLORSPACE_SRGB);
			$this->_image->save();
			return craft()->images->loadImage($path);
		}

		$this->_imageSourcePath = $path;
		$this->_extension = IOHelper::getExtension($path);

		if ($this->_extension == 'gif')
		{
			if (!craft()->images->isGd() && $this->_image->layers())
			{
				$this->_isAnimatedGif = true;
			}
		}

		$this->_resizeHeight = $this->getHeight();
		$this->_resizeWidth = $this->getWidth();

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

		if ($this->_isAnimatedGif)
		{
			$this->_image->layers()->coalesce();

			// Create a new image instance to avoid object references messing up our dimensions.
			$newSize = new \Imagine\Image\Box($width, $height);
			$startingPoint = new \Imagine\Image\Point($x1, $y1);
			$gif = $this->_instance->create($newSize);
			$gif->layers()->remove(0);

			foreach ($this->_image->layers() as $layer)
			{
				$croppedLayer = $layer->crop($startingPoint, $newSize);
				$gif->layers()->add($croppedLayer);

				// Let's update dateUpdated in case this is going to take awhile.
				if ($index = craft()->assetTransforms->getActiveTransformIndexModel())
				{
					craft()->assetTransforms->storeTransformIndexData($index);
				}
			}

			$this->_image = $gif;
		}
		else
		{
			$this->_image->crop(new \Imagine\Image\Point($x1, $y1), new \Imagine\Image\Box($width, $height));
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

		list($verticalPosition, $horizontalPosition) = explode("-", $cropPositions);

		if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight)
		{
			// Scale first.
			$factor = min($this->getWidth() / $targetWidth, $this->getHeight() / $targetHeight);
			$newHeight = round($this->getHeight() / $factor);
			$newWidth = round($this->getWidth() / $factor);

			$this->resize($newWidth, $newHeight);

			// Now crop.
			if ($newWidth - $targetWidth > 0)
			{
				switch ($horizontalPosition)
				{
					case 'left':
					{
						$x1 = 0;
						$x2 = $x1 + $targetWidth;
						break;
					}
					case 'right':
					{
						$x2 = $newWidth;
						$x1 = $newWidth - $targetWidth;
						break;
					}
					default:
					{
						$x1 = round(($newWidth - $targetWidth) / 2);
						$x2 = $x1 + $targetWidth;
						break;
					}
				}

				$y1 = 0;
				$y2 = $y1 + $targetHeight;
			}
			elseif ($newHeight - $targetHeight > 0)
			{
				switch ($verticalPosition)
				{
					case 'top':
					{
						$y1 = 0;
						$y2 = $y1 + $targetHeight;
						break;
					}
					case 'bottom':
					{
						$y2 = $newHeight;
						$y1 = $newHeight - $targetHeight;
						break;
					}
					default:
					{
						$y1 = round(($newHeight - $targetHeight) / 2);
						$y2 = $y1 + $targetHeight;
						break;
					}
				}

				$x1 = 0;
				$x2 = $x1 + $targetWidth;
			}
			else
			{
				$x1 = round(($newWidth - $targetWidth) / 2);
				$x2 = $x1 + $targetWidth;
				$y1 = round(($newHeight - $targetHeight) / 2);
				$y2 = $y1 + $targetHeight;
			}

			$this->crop($x1, $x2, $y1, $y2);
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

		if ($this->_isAnimatedGif)
		{
			$this->_image->layers()->coalesce();

			// Create a new image instance to avoid object references messing up our dimensions.
			$newSize = new \Imagine\Image\Box($targetWidth, $targetHeight);
			$gif = $this->_instance->create($newSize);
			$gif->layers()->remove(0);

			foreach ($this->_image->layers() as $layer)
			{
				$resizedLayer = $layer->resize($newSize, $this->_getResizeFilter());
				$gif->layers()->add($resizedLayer);

				// Let's update dateUpdated in case this is going to take awhile.
				if ($index = craft()->assetTransforms->getActiveTransformIndexModel())
				{
					craft()->assetTransforms->storeTransformIndexData($index);
				}
			}

			$this->_image = $gif;
		}
		else
		{
			if (craft()->images->isImagick())
			{
				$this->_image->smartResize(new \Imagine\Image\Box($targetWidth, $targetHeight), (bool) craft()->config->get('preserveImageColorProfiles'), $this->_quality);
			}
			else
			{
				$this->_image->resize(new \Imagine\Image\Box($targetWidth, $targetHeight), $this->_getResizeFilter());
			}
		}

		return $this;
	}

	/**
	 * Rotate an image by degrees.
	 *
	 * @param int $degrees
	 *
	 * @return Image
	 */
	public function rotate($degrees)
	{
		$this->_image->rotate($degrees);

		return $this;
	}

	/**
	 * Set image quality.
	 *
	 * @param int $quality
	 *
	 * @return Image
	 */
	public function setQuality($quality)
	{
		$this->_quality = $quality;
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
		$extension = StringHelper::toLowerCase(IOHelper::getExtension($targetPath));

		$options = $this->_getSaveOptions(false, $extension);
		$targetPath = IOHelper::getFolderName($targetPath).IOHelper::getFileName($targetPath, false).'.'.IOHelper::getExtension($targetPath);

		if ($autoQuality && in_array($extension, array('jpeg', 'jpg', 'png')))
		{
			clearstatcache();
			craft()->config->maxPowerCaptain();

			$originalSize = IOHelper::getFileSize($this->_imageSourcePath);
			$tempFile = $this->_autoGuessImageQuality($targetPath, $originalSize, $extension, 0, 200);
			IOHelper::move($tempFile, $targetPath, true);
		}
		else
		{
			$this->_image->save($targetPath, $options);
		}

		return true;
	}

	/**
	 * Load an image from an SVG string.
	 *
	 * @param $svgContent
	 *
	 * @return Image
	 */
	public function loadFromSVG($svgContent)
	{
		try
		{
			$this->_image = $this->_instance->load($svgContent);
		}
		catch (\Imagine\Exception\RuntimeException $e)
		{
			// Invalid SVG. Maybe it's missing its DTD?
			$svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svgContent;
			$this->_image = $this->_instance->load($svgContent);
		}

		return $this;
	}

	/**
	 * Returns true if Imagick is installed and says that the image is transparent.
	 *
	 * @return bool
	 */
	public function isTransparent()
	{
		if (craft()->images->isImagick() && method_exists("Imagick", "getImageAlphaChannel"))
		{
			return $this->_image->getImagick()->getImageAlphaChannel();
		}

		return false;
	}

	/**
	 * Return EXIF metadata for a file by it's path
	 *
	 * @param $filePath
	 *
	 * @return array
	 */
	public function getExifMetadata($filePath)
	{
		try
		{
			$exifReader = new \Imagine\Image\Metadata\ExifMetadataReader();
			$this->_instance->setMetadataReader($exifReader);
			$exif = $this->_instance->open($filePath)->metadata();

			return $exif->toArray();
		}
		catch (\Imagine\Exception\NotSupportedException $exception)
		{
			Craft::log($exception->getMessage(), LogLevel::Error);

			return array();
		}
	}

	/**
	 * Set properties for text drawing on the image.
	 *
	 * @param $fontFile string path to the font file on server
	 * @param $size     int    font size to use
	 * @param $color    string font color to use in hex format
	 *
	 * @return null
	 */
	public function setFontProperties($fontFile, $size, $color)
	{
		if (empty($this->_palette))
		{
			$this->_palette = new \Imagine\Image\Palette\RGB();
		}

		$this->_font = $this->_instance->font($fontFile, $size, $this->_palette->color($color));
	}

	/**
	 * Get the bounding text box for a text string and an angle
	 *
	 * @param $text
	 * @param int $angle
	 *
	 * @throws Exception
	 * @return \Imagine\Image\BoxInterface
	 */
	public function getTextBox($text, $angle = 0)
	{
		if (empty($this->_font))
		{
			throw new Exception(Craft::t("No font properties have been set. Call Image::setFontProperties() first."));
		}

		return $this->_font->box($text, $angle);
	}

	/**
	 * Write text on an image
	 *
	 * @param     $text
	 * @param     $x
	 * @param     $y
	 * @param int $angle
	 *
	 * @return null
	 * @throws Exception
	 */
	public function writeText($text, $x, $y, $angle = 0)
	{

		if (empty($this->_font))
		{
			throw new Exception(Craft::t("No font properties have been set. Call Image::setFontProperties() first."));
		}

		$point = new \Imagine\Image\Point($x, $y);

		$this->_image->draw()->text($text, $this->_font, $point, $angle);
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param     $tempFileName
	 * @param     $originalSize
	 * @param     $extension
	 * @param     $minQuality
	 * @param     $maxQuality
	 * @param int $step
	 *
	 * @return string $path the resulting file path
	 */
	private function _autoGuessImageQuality($tempFileName, $originalSize, $extension, $minQuality, $maxQuality, $step = 0)
	{
		if ($step == 0)
		{
			$tempFileName = IOHelper::getFolderName($tempFileName).IOHelper::getFileName($tempFileName, false).'-temp.'.$extension;
		}

		// Find our target quality by splitting the min and max qualities
		$midQuality = (int)ceil($minQuality + (($maxQuality - $minQuality) / 2));

		// Set the min and max acceptable ranges. .10 means anything between 90% and 110% of the original file size is acceptable.
		$acceptableRange = .10;

		clearstatcache();

		// Generate a new temp image and get it's file size.
		$this->_image->save($tempFileName, $this->_getSaveOptions($midQuality, $extension));
		$newFileSize = IOHelper::getFileSize($tempFileName);

		// If we're on step 10 OR we're within our acceptable range threshold OR midQuality = maxQuality (1 == 1),
		// let's use the current image.
		if ($step == 10 || abs(1 - $originalSize / $newFileSize) < $acceptableRange || $midQuality == $maxQuality)
		{
			clearstatcache();

			// Generate one last time.
			$this->_image->save($tempFileName, $this->_getSaveOptions($midQuality));
			return $tempFileName;
		}

		$step++;

		if ($newFileSize > $originalSize)
		{
			return $this->_autoGuessImageQuality($tempFileName, $originalSize, $extension, $minQuality, $midQuality, $step);
		}
		// Too much.
		else
		{
			return $this->_autoGuessImageQuality($tempFileName, $originalSize, $extension, $midQuality, $maxQuality, $step);
		}
	}

	/**
	 * @return mixed
	 */
	private function _getResizeFilter()
	{
		return (craft()->images->isGd() ? \Imagine\Image\ImageInterface::FILTER_UNDEFINED : \Imagine\Image\ImageInterface::FILTER_LANCZOS);
	}

	/**
	 * Get save options.
	 *
	 * @param int|null $quality
	 * @param string   $extension
	 * @return array
	 */
	private function _getSaveOptions($quality = null, $extension = null)
	{
		// Because it's possible for someone to set the quality to 0.
		$quality = ($quality === null || $quality === false ? $this->_quality : $quality);
		$extension = (!$extension ? $this->getExtension() : $extension);

		switch ($extension)
		{
			case 'jpeg':
			case 'jpg':
			{
				return array('jpeg_quality' => $quality, 'flatten' => true);
			}

			case 'gif':
			{
				$options = array('animated' => $this->_isAnimatedGif);

				return $options;
			}

			case 'png':
			{
				// Valid PNG quality settings are 0-9, so normalize and flip, because we're talking about compression
				// levels, not quality, like jpg and gif.
				$normalizedQuality = round(($quality * 9) / 100);
				$normalizedQuality = 9 - $normalizedQuality;

				if ($normalizedQuality < 0)
				{
					$normalizedQuality = 0;
				}

				if ($normalizedQuality > 9)
				{
					$normalizedQuality = 9;
				}

				$options = array('png_compression_level' => $normalizedQuality, 'flatten' => false);
				$pngInfo = ImageHelper::getPngImageInfo($this->_imageSourcePath);

				// Even though a 2 channel PNG is valid (Grayscale with alpha channel), Imagick doesn't recognize it as
				// a valid format: http://www.imagemagick.org/script/formats.php
				// So 2 channel PNGs get converted to 4 channel.

				if (is_array($pngInfo) && isset($pngInfo['channels']) && $pngInfo['channels'] !== 2)
				{
					$format = 'png'.(8 * $pngInfo['channels']);
				}
				else
				{
					$format = 'png32';
				}

				$options['png_format'] = $format;

				return $options;
			}

			default:
			{
				return array();
			}
		}
	}
}
