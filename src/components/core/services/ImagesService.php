<?php
namespace Blocks;

/**
 * Service for image operations
 */
class ImagesService extends BaseApplicationComponent
{

	/**
	 * Return image resource from path
	 * @param $path
	 * @throws \Exception
	 * @return ImageResource
	 */
	public function getResourceFromPath($path)
	{
		return new ImageResource($path);
	}

}
