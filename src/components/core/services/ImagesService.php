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
	 * @return bool|resource
	 * @throws \Exception
	 */
	public function getResourceFromPath($path)
	{
		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

		$image = false;

		switch ($extension)
		{
			case 'jpg':
			case 'jpeg':
				$image = imagecreatefromjpeg($path);
				break;
			case 'gif':
				$image = imagecreatefromgif($path);
				break;
			case 'png':
				$image = imagecreatefrompng($path);
				break;
		}
		if (!$image)
		{
			throw new \Exception(Blocks::t("The file extension was not recognized"));
		}

		return $image;
	}

	/**
	 * Preserves transparency for an output image resource, based on an image resource and file extensions
	 * @param $image
	 * @param $output
	 * @param $extension
	 */
	public function preserveTransparency($sourceImage, &$output, $extension)
	{
		// keep transparency for gifs and jpegs
		if (in_array($extension, array('gif', 'png')))
		{
			$transparencyIndex = imagecolortransparent($sourceImage);

			// if the index is set
			if ($transparencyIndex >= 0)
			{
				$transparentColor = imagecolorsforindex($sourceImage, $transparencyIndex);
				$transparencyIndex = imagecolorallocate($output, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
				imagefill($output, 0, 0, $transparencyIndex);
				imagecolortransparent($output, $transparencyIndex);
			}
			// png, baby
			elseif ($extension == 'png')
			{
				imagealphablending($output, false);
				$color = imagecolorallocatealpha($output, 0, 0, 0, 127);
				imagefill($output, 0, 0, $color);
				imagesavealpha($output, true);
			}
		}
	}

	/**
	 * Save an image resource to the target path
	 * @param $imageResource
	 * @param $targetPath
	 * @return bool
	 */
	public function saveResourceToPath($imageResource, $targetPath)
	{
		$extension = pathinfo($targetPath, PATHINFO_EXTENSION);

		$result = false;
		switch ($extension)
		{
			case 'jpg':
				$result = imagejpeg($imageResource, $targetPath, 100);
				break;
			case 'gif':
				$result = imagegif($imageResource, $targetPath);
				break;
			case 'png':
				$result = imagepng($imageResource, $targetPath, 5);
				break;
		}

		return $result;
	}

}
