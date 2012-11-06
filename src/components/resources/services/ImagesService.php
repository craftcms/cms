<?php
namespace Blocks;

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
		$image->loadImage($path);
		return $image;
	}
}
