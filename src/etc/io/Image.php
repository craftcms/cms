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

	/**
	 * Return a list of accepted extensions
	 * @return array
	 */
	public static function getAcceptedExtensions()
	{
		return array('jpg', 'jpeg', 'gif', 'png');
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

		$this->_extension = IOHelper::getExtension($path);

		if (!craft()->images->setMemoryForImage($path))
		{
			throw new Exception(Craft::t("Not enough memory available to perform this image operation."));
		}

		$imageInfo = @getimagesize($path);
		if (!is_array($imageInfo))
		{
			throw new Exception(Craft::t('The file “{path}” does not appear to be an image.', array('path' => $path)));
		}

		switch ($this->_extension)
		{
			case 'jpg':
			case 'jpeg':
			{
				if ($imageInfo[2] == IMAGETYPE_JPEG)
				{
					$this->_image = imagecreatefromjpeg($path);
				}
				else
				{
					throw new Exception(Craft::t('The file "{path}" does not appear to be a valid JPEG image.', array('path' => $path)));
				}
				break;
			}

			case 'gif':
			{
				if ($imageInfo[2] == IMAGETYPE_GIF)
				{
					$this->_image = imagecreatefromgif($path);
				}
				else
				{
					throw new Exception(Craft::t('The file "{path}" does not appear to be a valid GIF image.', array('path' => $path)));
				}
				break;
			}

			case 'png':
			{
				if ($imageInfo[2] == IMAGETYPE_PNG)
				{
					$this->_image = imagecreatefrompng($path);
				}
				else
				{
					throw new Exception(Craft::t('The file "{path}" does not appear to be a valid PNG image.', array('path' => $path)));
				}
				break;
			}

			default:
			{
				throw new Exception(Craft::t('The file “{path}” does not appear to be an image.', array('path' => $path)));
			}
		}

		$this->_imageSourcePath = $path;

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

		$output = $this->_getCanvas($width, $height);

		imagecopyresampled($output, $this->_image, 0, 0, $x1, $y1, $width, $height, $width, $height);

		$this->_image = $output;

		return $this;
	}

	/**
	 * Scale the image to fit within the specified size.
	 *
	 * @param $width
	 * @param $height
	 * @param bool $scaleIfSmaller
	 * @return Image
	 */
	public function scaleToFit($width, $height = null, $scaleIfSmaller = true)
	{
		$this->_formatDimensions($width, $height);

		if ($scaleIfSmaller || imagesx($this->_image) > $width || imagesy($this->_image) > $height)
		{
			$factor = max(imagesx($this->_image) / $width, imagesy($this->_image) / $height);
			$this->_doResize(round(imagesx($this->_image) / $factor), round(imagesy($this->_image) / $factor));
		}

		return $this;
	}

	/**
	 * Scale and crop image to exactly fit the specified size.
	 *
	 * @param        $width
	 * @param        $height
	 * @param bool   $scaleIfSmaller
	 * @param string $cropPositions
	 * @return Image
	 */
	public function scaleAndCrop($width, $height = null, $scaleIfSmaller = true, $cropPositions = 'center-center')
	{
		$this->_formatDimensions($width, $height);

		list($verticalPosition, $horizontalPosition) = explode("-", $cropPositions);

		if ($scaleIfSmaller || imagesx($this->_image) > $width || imagesy($this->_image) > $height)
		{
			$factor = min(imagesx($this->_image) / $width, imagesy($this->_image) / $height);
			$newHeight = round(imagesy($this->_image) / $factor);
			$newWidth = round(imagesx($this->_image) / $factor);
			$this->_doResize($newWidth, $newHeight);

			if ($newWidth - $width > 0)
			{
				switch ($horizontalPosition)
				{
					case 'left':
					{
						$x1 = 0;
						$x2 = $x1 + $width;
						break;
					}
					case 'right':
					{
						$x2 = $newWidth;
						$x1 = $newWidth - $width;
						break;
					}
					default:
					{
						$x1 = round(($newWidth - $width) / 2);
						$x2 = $x1 + $width;
						break;
					}
				}
				$y1 = 0;
				$y2 = $y1 + $height;
			}
			elseif ($newHeight - $height > 0)
			{
				switch ($verticalPosition)
				{
					case 'top':
					{
						$y1 = 0;
						$y2 = $y1 + $height;
						break;
					}
					case 'bottom':
					{
						$y2 = $newHeight;
						$y1 = $newHeight - $height;
						break;
					}
					default:
					{
						$y1 = round(($newHeight - $height) / 2);
						$y2 = $y1 + $height;
						break;
					}
				}
				$x1 = 0;
				$x2 = $x1 + $width;
			}
			else
			{
				$x1 = round(($newWidth - $width) / 2);
				$x2 = $x1 + $width;
				$y1 = round(($newHeight - $height) / 2);
				$y2 = $y1 + $height;
			}

			$this->crop($x1, $x2, $y1, $y2);
		}

		return $this;
	}

	/**
	 * Resizes the image. If $height is not specified, it will default to $width, creating a square.
	 *
	 * @param int $width
	 * @param int|null $height
	 * @return Image
	 */
	public function resizeTo($width, $height = null)
	{
		$this->_formatDimensions($width, $height);
		$this->_doResize($width, $height);

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
		$extension = IOHelper::getExtension($targetPath);

		// Just in case no image operation was run, we try to preserve transparency here as well.
		$this->_image = $this->_preserveTransparency($this->_image);

		$result = false;

		switch ($extension)
		{
			case 'jpeg':
			case 'jpg':
			{
				if ($sanitizeAndAutoQuality)
				{
					clearstatcache();
					$originalSize = IOHelper::getFileSize($targetPath);
					$result = $this->_autoGuessImageQuality($targetPath, $originalSize, $extension, 0, 200);
				}
				else
				{
					$this->_generateImage($extension, $targetPath, 75);
				}

				break;
			}

			case 'gif':
			{
				$result = imagegif($this->_image, $targetPath);
				break;
			}

			case 'png':
			{
				$this->_generateImage($extension, $targetPath, 9);
				break;
			}
		}

		return $result;
	}

	/**
	 * Calculate missing dimension.
	 *
	 * @param $width
	 * @param $height
	 * @param $sourceWidth
	 * @param $sourceHeight
	 * @return array Array of the width and height.
	 */
	public static function calculateMissingDimension($width, $height, $sourceWidth, $sourceHeight)
	{
		$factor = $sourceWidth / $sourceHeight;

		if (empty($height))
		{
			$height = round($width / $factor);
		}
		else if (empty($width))
		{
			$width = round($height * $factor);
		}

		return array($width, $height);
	}

	/**
	 * Perform the actual resize.
	 *
	 * @param $width
	 * @param $height
	 */
	private function _doResize($width, $height)
	{
		$output = $this->_getCanvas($width, $height);

		imagecopyresampled($output, $this->_image, 0, 0, 0, 0, $width, $height, imagesx($this->_image), imagesy($this->_image));

		$this->_image = $output;
	}

	/**
	 * Format dimensions.
	 *
	 * @param $width
	 * @param $height
	 * @throws Exception
	 */
	private function _formatDimensions(&$width, &$height = null)
	{
		if (preg_match('/^(?P<width>[0-9]+|AUTO)x(?P<height>[0-9]+|AUTO)/', $width, $matches))
		{
			$width  = $matches['width']  != 'AUTO' ? $matches['width']  : null;
			$height = $matches['height'] != 'AUTO' ? $matches['height'] : null;
		}

		if (!$height || !$width)
		{
			list($width, $height) = static::calculateMissingDimension($width, $height, imagesx($this->_image), imagesy($this->_image));
		}
	}

	/**
	 * Get canvas.
	 *
	 * @param $width
	 * @param $height
	 * @return resource
	 * @throws Exception
	 */
	private function _getCanvas($width, $height)
	{
		if (!craft()->images->setMemoryForImage($this->_imageSourcePath))
		{
			throw new Exception(Craft::t("Not enough memory available to perform this image operation."));
		}

		return $this->_preserveTransparency(imagecreatetruecolor($width, $height));
	}

	/**
	 * Preserves transparency depending on the file extension.
	 *
	 * @param resource $image
	 * @return mixed $image
	 */
	private function _preserveTransparency($image)
	{
		// Preserve transparency for GIFs and PNGs
		if (in_array($this->_extension, array('gif', 'png')))
		{
			$transparencyIndex = imagecolortransparent($this->_image);

			// Is the index set?
			if ($transparencyIndex >= 0)
			{
				$transparentColor = imagecolorsforindex($this->_image, $transparencyIndex);
				$transparencyIndex = imagecolorallocate($image, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				imagefill($image, 0, 0, $transparencyIndex);
				imagecolortransparent($this->_image, $transparencyIndex);
			}
			// PNG, baby
			elseif ($this->_extension == 'png')
			{
				imagealphablending($image, false);
				$color = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $color);
				imagesavealpha($image, true);
			}
		}

		return $image;
	}

	/**
	 *
	 */
	private function _autoGuessImageQuality($targetPath, $originalSize, $extension, $minQuality, $maxQuality, $step = 0)
	{
		$tempFileName = IOHelper::getFolderName($targetPath).IOHelper::getFileName($targetPath, false).'-temp.'.$extension;

		// Find our target quality by splitting the min and max qualities
		$midQuality = (int)ceil($minQuality + (($maxQuality - $minQuality) / 2));

		// Set the min and max acceptable ranges. .10 means anything between 90% and 110% of the original file size is acceptable.
		$acceptableRange = .10;

		// Generate a new temp image and get it's file size.
		$this->_generateImage($extension, $tempFileName, $midQuality);
		$newFileSize = IOHelper::getFileSize($tempFileName);

		// If we're on step 10 or we're within our acceptable range threshold, let's use the current image.
		if ($step == 10 || abs(1 - $originalSize / $newFileSize) < $acceptableRange)
		{
			// Generate one last time.
			return $this->_generateImage($extension, $targetPath, $midQuality);
		}

		$step++;

		// Too little.
		if ($newFileSize > $originalSize)
		{
			return $this->_autoGuessImageQuality($targetPath, $originalSize, $extension, $minQuality, $midQuality, $step);
		}
		// Too much.
		else
		{
			return $this->_autoGuessImageQuality($targetPath, $originalSize, $extension, $midQuality, $maxQuality, $step);
		}
	}

	/**
	 * @param $extension
	 * @param $targetPath
	 * @param $targetQuality
	 * @return bool
	 */
	private function _generateImage($extension, $targetPath, $targetQuality)
	{
		switch ($extension)
		{
			case 'jpeg':
			case 'jpg':
			{
				$result = imagejpeg($this->_image, $targetPath, $targetQuality);
				break;
			}

			case 'gif':
			{
				$result = imagegif($this->_image, $targetPath);
				break;
			}

			case 'png':
			{
				$result = imagepng($this->_image, $targetPath, $targetQuality);
				break;
			}
		}

		return $result;
	}
}
