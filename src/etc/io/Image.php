<?php
namespace Craft;

/**
 * Image
 */
class Image
{
	private $_imageSourcePath;
	private $_image;
	private $_extension;
	private $_instance;
	private $_width;
	private $_height;
	private $_isGd;

	function __construct()
	{
		if (extension_loaded('imagick'))
		{
			$this->_instance = new \Imagine\Imagick\Imagine();
			$this->_isGd = false;
		}

		$this->_instance = new \Imagine\Gd\Imagine();
		$this->_isGd = true;
	}

	/**
	 * TODO?
	 * @return int
	 */
	public function getWidth()
	{
		return $this->_width;
	}

	/**
	 * TODO?
	 * @return int
	 */
	public function getHeight()
	{
		return $this->_height;
	}

	/**
	 * @return mixed
	 */
	public function getExtension()
	{
		return $this->_extension;
	}

	/**
	 * Loads an image from a file system path.
	 *
	 * @param string $path
	 * @return Image
	 * @throws Exception
	 */
	public function loadImage($path)
	{
		if (!IOHelper::fileExists($path))
		{
			throw new Exception(Craft::t('No file exists at the path “{path}”', array('path' => $path)));
		}

		if (!craft()->images->setMemoryForImage($path))
		{
			throw new Exception(Craft::t("Not enough memory available to perform this image operation."));
		}

		$imageInfo = @getimagesize($path);
		if (!is_array($imageInfo))
		{
			throw new Exception(Craft::t('The file “{path}” does not appear to be an image.', array('path' => $path)));
		}

		$this->_image = $this->_instance->open($path);
		$this->_extension = IOHelper::getExtension($path);
		$this->_imageSourcePath = $path;
		$this->_width = $this->_image->getSize()->getWidth();
		$this->_height = $this->_image->getSize()->getHeight();

		return $this;
	}

	/**
	 * Crops the image to the specified coordinates.
	 *
	 * @param int $x1
	 * @param int $x2
	 * @param int $y1
	 * @param int $y2
	 * @return Image
	 */
	public function crop($x1, $x2, $y1, $y2)
	{
		$width = $x2 - $x1;
		$height = $y2 - $y1;

		$this->_image->crop(new \Imagine\Image\Point($x1, $y1), new \Imagine\Image\Box($width, $height));

		return $this;
	}

	/**
	 * Scale the image to fit within the specified size.
	 *
	 * @param $targetWidth
	 * @param $targetHeight
	 * @param bool $scaleIfSmaller
	 * @return Image
	 */
	public function scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true)
	{
		$this->_normalizeDimensions($targetWidth, $targetHeight);

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
	 * @param        $targetWidth
	 * @param        $targetHeight
	 * @param bool   $scaleIfSmaller
	 * @param string $cropPositions
	 * @return Image
	 */
	public function scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center')
	{
		$this->_normalizeDimensions($targetWidth, $targetHeight);

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
	 * Resizes the image. If $height is not specified, it will default to $width, creating a square.
	 *
	 * @param int $targetWidth
	 * @param int|null $targetHeight
	 * @return Image
	 */
	public function resize($targetWidth, $targetHeight = null)
	{
		$this->_normalizeDimensions($targetWidth, $targetHeight);
		$this->_image->resize(new \Imagine\Image\Box($targetWidth, $targetHeight), $this->_getResizeFilter());

		return $this;
	}

	/**
	 * Saves the image to the target path.
	 *
	 * @param      $targetPath
	 * @param bool $sanitizeAndAutoQuality
	 * @return bool
	 */
	public function saveAs($targetPath, $sanitizeAndAutoQuality = false)
	{
		$extension = $this->getExtension();
		$options = $this->_getSaveOptions();

		if (($extension == 'jpeg' || $extension == 'jpg' || $extension == 'png') && $sanitizeAndAutoQuality)
		{
			clearstatcache();
			$originalSize = IOHelper::getFileSize($this->_imageSourcePath);
			$this->_autoGuessImageQuality($targetPath, $originalSize, $extension, 0, 200);
		}
		else
		{
			return $this->_image->save($targetPath, $options);
		}
	}

	/**
	 * Normalizes the given dimensions.  If width or height is set to 'AUTO', we calculate the missing dimension.
	 *
	 * @param $width
	 * @param $height
	 * @throws Exception
	 */
	private function _normalizeDimensions(&$width, &$height = null)
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

	/**
	 *
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
		$this->_image->save($tempFileName, $this->_getSaveOptions($midQuality));
		$newFileSize = IOHelper::getFileSize($tempFileName);

		// If we're on step 10 or we're within our acceptable range threshold, let's use the current image.
		if ($step == 10 || abs(1 - $originalSize / $newFileSize) < $acceptableRange)
		{
			clearstatcache();

			// Generate one last time.
			$this->_image->save($tempFileName, $this->_getSaveOptions($midQuality));
			return true;
		}

		$step++;

		// Too little.
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
		return ($this->_isGd ? \Imagine\Image\ImageInterface::FILTER_UNDEFINED : \Imagine\Image\ImageInterface::FILTER_LANCZOS);
	}

	/**
	 * @param null $quality
	 * @return array
	 */
	private function _getSaveOptions($quality = null)
	{
		$quality = (!$quality ? craft()->config->get('defaultImageQualityLevel') : $quality);

		switch ($this->getExtension())
		{
			case 'jpeg':
			case 'jpg':
			{
				return array('quality' => $quality, 'flatten' => true);
			}

			case 'gif':
			{
				return array('flatten' => false);
			}

			case 'png':
			{
				return array('quality' => $quality, 'flatten' => false);
			}

			default:
			{
				return array();
			}
		}
	}
}
