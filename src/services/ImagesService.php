<?php
namespace Craft;

/**
 * Service for image operations
 */
class ImagesService extends BaseApplicationComponent
{
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
