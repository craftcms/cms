<?php
namespace Blocks;

/**
 * Image
 */
class Image
{
	private $_imageSourcePath;
	private $_image;
	private $_extension;

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
			throw new Exception(Blocks::t('No file exists at the path “{path}”', array('path' => $path)));
		}

		$this->_extension = IOHelper::getExtension($path);

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
				throw new Exception(Blocks::t('The file “{path}” does not appear to be an image.', array('path' => $path)));
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
	 */
	public function scale($width, $height, $scaleIfSmaller = false)
	{
		if (imagesx($this->_image) > $width || imagesy($this->_image) > $height || $scaleIfSmaller)
		{
			$factor = max(imagesx($this->_image) / $width, imagesy($this->_image) / $height);
			$this->_doResize(imagesx($this->_image) / $factor, imagesy($this->_image) / $factor);
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
		if ($height === null)
		{
			$height = $width;
		}

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
		$image = $this->_preserveTransparency($this->_getCanvas($width, $height));

		imagecopyresampled($image, $this->_image, 0, 0, 0, 0, $width, $height, imagesx($this->_image), imagesy($this->_image));
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
		if (!blx()->images->setMemoryForImage($this->_imageSourcePath))
		{
			throw new Exception(Blocks::t("Not enough memory available to perform this image operation."));
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
}
