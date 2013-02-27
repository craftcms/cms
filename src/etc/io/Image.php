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

		switch ($this->_extension)
		{
			case 'jpg':
			case 'jpeg':
			{
				$this->_image = imagecreatefromjpeg($path);
				break;
			}

			case 'gif':
			{
				$this->_image = imagecreatefromgif($path);
				break;
			}

			case 'png':
			{
				$this->_image = imagecreatefrompng($path);
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
	 * Scale the image to fit the specified size.
	 *
	 * @param $width
	 * @param $height
	 * @param bool $scaleIfSmaller
	 * @return Image
	 */
	public function scale($width, $height = null, $scaleIfSmaller = false)
	{
		$this->_formatDimensions($width, $height);

		if (imagesx($this->_image) > $width || imagesy($this->_image) > $height || $scaleIfSmaller)
		{
			$factor = max(imagesx($this->_image) / $width, imagesy($this->_image) / $height);
			$this->_doResize(round(imagesx($this->_image) / $factor), round(imagesy($this->_image) / $factor));
		}

		return $this;
	}

	/**
	 * Scale and crop image to exactly fit the specified size.
	 *
	 * @param $width
	 * @param $height
	 * @param bool $scaleIfSmaller
	 * @return Image
	 */
	public function scaleAndCrop($width, $height = null, $scaleIfSmaller = false)
	{
		$this->_formatDimensions($width, $height);

		if (imagesx($this->_image) > $width || imagesy($this->_image) > $height || $scaleIfSmaller)
		{
			$factor = min(imagesx($this->_image) / $width, imagesy($this->_image) / $height);
			$newHeight = round(imagesy($this->_image) / $factor);
			$newWidth = round(imagesx($this->_image) / $factor);
			$this->_doResize($newWidth, $newHeight);

			$x1 = round($newWidth - $width) / 2;
			$x2 = $x1 + $width;
			$y1 = round($newHeight - $height) / 2;
			$y2 = $y1 + $height;

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
	 * Perform the actual resize.
	 *
	 * @param $width
	 * @param $height
	 */
	private function _doResize($width, $height)
	{
		$output = $this->_preserveTransparency($this->_getCanvas($width, $height));

		imagecopyresampled($output, $this->_image, 0, 0, 0, 0, $width, $height, imagesx($this->_image), imagesy($this->_image));

		$this->_image = $output;
	}

	/**
	 * Saves the image to the target path.
	 *
	 * @param $targetPath
	 * @return bool
	 */
	public function saveAs($targetPath)
	{

		$extension = IOHelper::getExtension($targetPath);

		$result = false;

		switch ($extension)
		{
			case 'jpeg':
			case 'jpg':
			{
				$result = imagejpeg($this->_image, $targetPath, 100);
				break;
			}

			case 'gif':
			{
				$result = imagegif($this->_image, $targetPath);
				break;
			}

			case 'png':
			{
				$result = imagepng($this->_image, $targetPath, 5);
				break;
			}
		}

		return $result;
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
	 * Format dimensions.
	 *
	 * @param $width
	 * @param $height
	 * @throws Exception
	 */
	private function _formatDimensions(&$width, &$height)
	{
		if (is_null($height))
		{
			if (preg_match('/^(?P<width>[0-9]+)x(?P<height>[0-9]+)/', $width, $matches))
			{
				$width = $matches['width'];
				$height = $matches['height'];
			}
			else
			{
				if (is_numeric($width))
				{
					$height = $width;
				}
				else
				{
					throw new Exception("Unrecognized image size");
				}
			}
		}
	}
}
