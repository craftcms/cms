<?php
namespace Blocks;

/**
 * Image
 */
class Image
{
	private $_sourceImage;
	private $_outputImage;
	private $_extension;

	/**
	 * Loads an image from a file system path.
	 *
	 * @param string $path
	 * @return Image
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
				$this->_sourceImage = imagecreatefromjpeg($path);
				break;
			}

			case 'gif':
			{
				$this->_sourceImage = imagecreatefromgif($path);
				break;
			}

			case 'png':
			{
				$this->_sourceImage = imagecreatefrompng($path);
				break;
			}

			default:
			{
				throw new Exception(Blocks::t('The file “{path}” does not appear to be an image.', array('path' => $path)));
			}
		}

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

		$this->_prepareCanvas($width, $height);
		$this->_preserveTransparency();

		imagecopyresampled($this->_outputImage, $this->_sourceImage, 0, 0, $x1, $y1, $width, $height, $width, $height);

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

		$this->_prepareCanvas($width, $height);
		$this->_preserveTransparency();

		imagecopyresampled($this->_outputImage, $this->_sourceImage, 0, 0, 0, 0, $width, $height, imagesx($this->_sourceImage), imagesy($this->_sourceImage));

		return $this;
	}

	/**
	 * Saves the image to the target path.
	 *
	 * @param $targetPath
	 * @return bool
	 */
	public function saveAs($targetPath)
	{
		// If no operations have been performed, save the source
		if (empty($this->_outputImage))
		{
			$this->_outputImage = $this->_sourceImage;
		}

		$extension = IOHelper::getExtension($targetPath);

		$result = false;

		switch ($extension)
		{
			case 'jpg':
			{
				$result = imagejpeg($this->_outputImage, $targetPath, 100);
				break;
			}

			case 'gif':
			{
				$result = imagegif($this->_outputImage, $targetPath);
				break;
			}

			case 'png':
			{
				$result = imagepng($this->_outputImage, $targetPath, 5);
				break;
			}
		}

		return $result;
	}

	/**
	 * Prepares the canvas.
	 *
	 * @access private
	 * @param $width
	 * @param $height
	 */
	private function _prepareCanvas($width, $height)
	{
		if (empty($this->_outputImage))
		{
			$this->_outputImage = imagecreatetruecolor($width, $height);
		}
	}

	/**
	 * Preserves transparency depending on the file extension.
	 *
	 * @access private
	 */
	private function _preserveTransparency()
	{
		// Preserve transparency for GIFs and PNGs
		if (in_array($this->_extension, array('gif', 'png')))
		{
			$transparencyIndex = imagecolortransparent($this->_sourceImage);

			// Is the index set?
			if ($transparencyIndex >= 0)
			{
				$transparentColor = imagecolorsforindex($this->_sourceImage, $transparencyIndex);
				$transparencyIndex = imagecolorallocate($this->_outputImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				imagefill($this->_outputImage, 0, 0, $transparencyIndex);
				imagecolortransparent($this->_outputImage, $transparencyIndex);
			}
			// PNG, baby
			elseif ($this->_extension == 'png')
			{
				imagealphablending($this->_outputImage, false);
				$color = imagecolorallocatealpha($this->_outputImage, 0, 0, 0, 127);
				imagefill($this->_outputImage, 0, 0, $color);
				imagesavealpha($this->_outputImage, true);
			}
		}
	}
}
