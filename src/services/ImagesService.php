<?php
namespace Craft;

/**
 * Service for image operations
 */
class ImagesService extends BaseApplicationComponent
{
	private $_isGd = null;

	/**
	 * Returns whether image manipulations will be performed using GD or not.
	 *
	 * @return bool|null
	 */
	public function isGd()
	{
		if ($this->_isGd === null)
		{
			if (extension_loaded('imagick'))
			{
				// Taken from Imagick\Imagine() constructor.
				$imagick = new \Imagick();
				$v = $imagick->getVersion();
				list($version, $year, $month, $day, $q, $website) = sscanf($v['versionString'], 'ImageMagick %s %04d-%02d-%02d %s %s');

				// Update this if Imagine updates theirs.
				if (version_compare('6.2.9', $version) <= 0)
				{
					$this->_isGd = false;
				}
				else
				{
					$this->_isGd = true;
				}
			}
			else
			{
				$this->_isGd = true;
			}
		}

		return $this->_isGd;
	}

	/**
	 * Returns whether image manipulations will be performed using Imagick or not.
	 *
	 * @return bool
	 */
	public function isImagick()
	{
		return !$this->isGd();
	}

	/**
	 * Loads an image from a file system path.
	 *
	 * @param $path
	 * @throws \Exception
	 * @return Image
	 */
	public function loadImage($path)
	{
		$image = new Image();
		$this->setMemoryForImage($path);
		$image->loadImage($path);
		return $image;
	}

	/**
	 * Sets the memory needed for an image file. Adapted from http://www.php.net/manual/en/function.imagecreatefromjpeg.php#64155.
	 *
	 * @param $filename
	 * @return bool
	 */
	public function setMemoryForImage($filename)
	{
		$imageInfo = getimagesize($filename);
		$MB = 1048576;
		$K64 = 65536;
		$tweakFactor = 1.7;
		$bits = isset($imageInfo['bits']) ? $imageInfo['bits'] : 8;
		$channels = isset($imageInfo['channels']) ? $imageInfo['channels'] : 4;
		$memoryNeeded = round(($imageInfo[0] * $imageInfo[1] * $bits  * $channels / 8 + $K64) * $tweakFactor);

		$memoryLimitMB = (int)ini_get('memory_limit');
		$memoryLimit = $memoryLimitMB * $MB;

		if (function_exists('memory_get_usage'))
		{
			if (memory_get_usage() + $memoryNeeded > $memoryLimit)
			{
				$newLimit = $memoryLimitMB + ceil((memory_get_usage() + $memoryNeeded - $memoryLimit) / $MB);
				return (bool)ini_set( 'memory_limit', $newLimit.'M' );
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Cleans an image by it's path, clearing embedded JS and PHP code.
	 *
	 * @param $filePath
	 * @return bool
	 */
	public function cleanImage($filePath)
	{
		$image = new Image();
		return $image->loadImage($filePath)->saveAs($filePath, true);
	}
}
