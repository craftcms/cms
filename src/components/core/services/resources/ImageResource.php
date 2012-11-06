<?php
namespace Blocks;

/**
 * Image resource
 */
class ImageResource
{

	private $_sourceImage = null;
	private $_outputImage = null;
	private $_extension = '';

	public function __construct($path)
	{
		if (!IOHelper::fileExists($path))
		{
			throw new Exception(Blocks::t("File doesn't exist!"));
		}

		$this->_extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		switch ($this->_extension)
		{
			case 'jpg':
			case 'jpeg':
				$this->_sourceImage = imagecreatefromjpeg($path);
				break;
			case 'gif':
				$this->_sourceImage = imagecreatefromgif($path);
				break;
			case 'png':
				$this->_sourceImage = imagecreatefrompng($path);
				break;
		}

		if (empty($this->_sourceImage))
		{
			throw new Exception(Blocks::t("The file extension was not recognized"));
		}

	}

	/**
	 * Crop an image to the size
	 * @param $x1
	 * @param $x2
	 * @param $y1
	 * @param $y2
	 */
	public function crop($x1, $x2, $y1, $y2)
	{

		$width = $x2 - $x1;
		$height = $y2 - $y1;

		$this->_prepareCanvas($width, $height);
		$this->_preserveTransparency();

		imagecopyresampled($this->_outputImage, $this->_sourceImage, 0, 0, $x1, $y1, $width, $height, $width, $height);
	}

	public function resizeTo($width, $height = null)
	{
		if (is_null($height))
		{
			$height = $width;
		}

		$this->_prepareCanvas($width, $height);
		$this->_preserveTransparency();

		imagecopyresampled($this->_outputImage, $this->_sourceImage, 0, 0, 0, 0, $width, $height, imagesx($this->_sourceImage), imagesy($this->_sourceImage));
	}

	/**
	 * Prepare canvas
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
	 * Preserves transparency for a file extension
	 */
	private function _preserveTransparency()
	{

		// keep transparency for gifs and jpegs
		if (in_array($this->_extension, array('gif', 'png')))
		{
			$transparencyIndex = imagecolortransparent($this->_sourceImage);

			// if the index is set
			if ($transparencyIndex >= 0)
			{
				$transparentColor = imagecolorsforindex($this->_sourceImage, $transparencyIndex);
				$transparencyIndex = imagecolorallocate($this->_outputImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				imagefill($this->_outputImage, 0, 0, $transparencyIndex);
				imagecolortransparent($this->_outputImage, $transparencyIndex);
			}
			// png, baby
			elseif ($this->_extension == 'png')
			{
				imagealphablending($this->_outputImage, false);
				$color = imagecolorallocatealpha($this->_outputImage, 0, 0, 0, 127);
				imagefill($this->_outputImage, 0, 0, $color);
				imagesavealpha($this->_outputImage, true);
			}
		}
	}

	/**
	 * Saves to the target path
	 * @param $targetPath
	 * @return bool
	 */
	public function saveAs($targetPath)
	{
		// if no operations are done, save the source
		if (empty($this->_outputImage))
		{
			$this->_outputImage = $this->_sourceImage;
		}
		$extension = pathinfo($targetPath, PATHINFO_EXTENSION);

		$result = false;
		switch ($extension)
		{
			case 'jpg':
				$result = imagejpeg($this->_outputImage, $targetPath, 100);
				break;
			case 'gif':
				$result = imagegif($this->_outputImage, $targetPath);
				break;
			case 'png':
				$result = imagepng($this->_outputImage, $targetPath, 5);
				break;
		}

		return $result;
	}

}
