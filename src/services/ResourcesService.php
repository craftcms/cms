<?php
namespace Craft;

/**
 *
 */
class ResourcesService extends BaseApplicationComponent
{
	public $dateParam;

	/**
	 * Resolves a resource path to the actual file system path, or returns false if the resource cannot be found.
	 *
	 * @param string $path
	 * @return string
	 */
	public function getResourcePath($path)
	{
		$segs = explode('/', $path);

		// Special resource routing
		if (isset($segs[0]))
		{
			switch($segs[0])
			{
				case 'js':
				{
					// Route to js/compressed/ if useCompressedJs is enabled
					if (craft()->config->get('useCompressedJs'))
					{
						array_splice($segs, 1, 0, 'compressed');
						$path = implode('/', $segs);
					}
					break;
				}

				case 'userphotos':
				{
					if (isset($segs[1]) && $segs[1] == 'temp')
					{
						if (!isset($segs[2]))
						{
							return false;
						}

						return craft()->path->getTempUploadsPath().'userphotos/'.$segs[2].'/'.$segs[3];
					}
					else
					{
						if (!isset($segs[3]))
						{
							return false;
						}

						$username = IOHelper::cleanFilename($segs[1]);
						$size     = IOHelper::cleanFilename($segs[2]);
						$filename = IOHelper::cleanFilename($segs[3]);

						$userPhotosPath = craft()->path->getUserPhotosPath().$username.'/';
						$sizedPhotoFolder = $userPhotosPath.$size.'/';
						$sizedPhotoPath = $sizedPhotoFolder.$filename;

						// If the photo doesn't exist at this size, create it.
						if (!IOHelper::fileExists($sizedPhotoPath))
						{
							$originalPhotoPath = $userPhotosPath.'original/'.$filename;

							if (!IOHelper::fileExists($originalPhotoPath))
							{
								return false;
							}

							IOHelper::ensureFolderExists($sizedPhotoFolder);

							craft()->images->loadImage($originalPhotoPath)
								->resizeTo($size)
								->saveAs($sizedPhotoPath);
						}

						return $sizedPhotoPath;
					}
				}

				case 'tempuploads':
				{
					array_shift($segs);
					return craft()->path->getTempUploadsPath().implode('/', $segs);
				}

				case 'assetthumbs':
				{
					if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[1]) || !is_numeric($segs[2]))
					{
						return false;
					}

					$fileModel = craft()->assets->getFileById($segs[1]);
					if (empty($fileModel))
					{
						return false;
					}

					$sourceType = craft()->assetSources->getSourceTypeById($fileModel->sourceId);

					$size = $segs[2];

					$thumbFolder = craft()->path->getAssetsThumbsPath().$size.'/';
					IOHelper::ensureFolderExists($thumbFolder);

					$thumbPath = $thumbFolder.$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);

					if (!IOHelper::fileExists($thumbPath))
					{
						$sourcePath = $sourceType->getImageSourcePath($fileModel);
						if (!IOHelper::fileExists($sourcePath))
						{
							return false;
						}
						craft()->images->loadImage($sourcePath)
							->scaleAndCrop($size, $size)
							->saveAs($thumbPath);
					}

					return $thumbPath;
				}

				case 'icons':
				{
					if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[2]) || !preg_match('/^(?P<extension>[a-z_0-9]+)/i', $segs[1]))
					{
						return false;
					}

					$ext = strtolower($segs[1]);
					$size = $segs[2];

					$iconPath = $this->_getIconPath($ext, $size);

					return $iconPath;
				}

				case 'logo':
				{
					return craft()->path->getStoragePath().implode('/', $segs);
				}
			}
		}

		// Check app/resources folder first.
		$appResourcePath = craft()->path->getResourcesPath().$path;

		if (IOHelper::fileExists($appResourcePath))
		{
			return $appResourcePath;
		}

		// See if the first segment is a plugin handle.
		if (isset($segs[0]))
		{
			$pluginResourcePath = craft()->path->getPluginsPath().$segs[0].'/'.'resources/'.implode('/', array_splice($segs, 1));

			if (IOHelper::fileExists($pluginResourcePath))
			{
				return $pluginResourcePath;
			}
		}

		// Maybe a plugin wants to do something custom with this URL
		$pluginPaths = craft()->plugins->call('getResourcePath', array($path));
		foreach ($pluginPaths as $path)
		{
			if ($path && IOHelper::fileExists($path))
			{
				return $path;
			}
		}

		// Couldn't find the file
		return false;
	}

	/**
	 * Sends a resource back to the browser.
	 *
	 * @param string $path
	 * @throws HttpException
	 */
	public function sendResource($path)
	{
		if (PathHelper::ensurePathIsContained($path) === false)
		{
			throw new HttpException(403);
		}

		$path = $this->getResourcePath($path);

		if ($path === false || !IOHelper::fileExists($path))
		{
			throw new HttpException(404);
		}

		$content = IOHelper::getFileContents($path);

		// Normalize URLs in CSS files
		$mimeType = IOHelper::getMimeTypeByExtension($path);
		if (strpos($mimeType, 'css') !== false)
		{
			$content = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', array(&$this, '_normalizeCssUrl'), $content);
		}

		if (!craft()->config->get('useXSendFile'))
		{
			$options['forceDownload'] = false;

			if (craft()->request->getQuery($this->dateParam))
			{
				$options['cache'] = true;
			}

			craft()->request->sendFile($path, $content, $options);
		}
		else
		{
			craft()->request->xSendFile($path);
		}

		exit(1);
	}

	/**
	 * @access private
	 * @param $match
	 * @return string
	 */
	private function _normalizeCssUrl($match)
	{
		// ignore root-relative, absolute, and data: URLs
		if (preg_match('/^(\/|https?:\/\/|data:)/', $match[3]))
		{
			return $match[0];
		}

		$url = IOHelper::getFolderName(craft()->request->getPath()).$match[3];

		// Make sure this is a resource URL
		$resourceTrigger = craft()->config->get('resourceTrigger');
		$resourceTriggerPos = strpos($url, $resourceTrigger);
		if ($resourceTriggerPos !== false)
		{
			// Give UrlHelper a chance to add the timestamp
			$path = substr($url, $resourceTriggerPos+strlen($resourceTrigger));
			$url = UrlHelper::getResourceUrl($path);
		}

		return $match[1].$url.$match[4];
	}

	/**
	 * Get icon path for an extension and size
	 *
	 * @param $ext
	 * @param $size
	 * @return string
	 */
	private function _getIconPath($ext, $size)
	{
		if (strlen($ext) > 4)
		{
			$ext = '';
		}

		$extAlias = array(
			'docx' => 'doc',
			'xlsx' => 'xls',
			'pptx' => 'ppt',
			'jpeg' => 'jpg',
			'html' => 'htm',
		);

		if (isset($extAlias[$ext]))
		{
			$ext = $extAlias[$ext];
		}

		$sizeFolder = craft()->path->getAssetsIconsPath().$size;

		// See if we have the icon already
		$iconLocation = $sizeFolder.'/'.$ext.'.png';

		if (IOHelper::fileExists($iconLocation))
		{
			return $iconLocation;
		}

		// We are going to need that folder to exist.
		IOHelper::ensureFolderExists($sizeFolder);

		// Determine the closest source size
		$sourceSizes = array(
			array('size' => 28, 'extSize' => 4, 'extY' => 20),
			array('size' => 56, 'extSize' => 8, 'extY' => 40),
			array('size' => 178, 'extSize' => 30, 'extY' => 142),
			array('size' => 350, 'extSize' => 60, 'extY' => 280)
		);

		foreach ($sourceSizes as $sourceSize)
		{
			if ($sourceSize['size'] >= $size)
			{
				break;
			}
		}

		$sourceFolder = craft()->path->getAssetsIconsPath().$sourceSize['size'];

		// Do we have a source icon that we can resize?
		$sourceIconLocation = $sourceFolder.'/'.$ext.'.png';
		if (!IOHelper::fileExists($sourceIconLocation))
		{
			$sourceFile = craft()->path->getAppPath().'etc/assets/fileicons/'.$sourceSize['size'].'.png';
			$image = imagecreatefrompng($sourceFile);
			// Text placement.
			if ($ext)
			{
				$color = imagecolorallocate($image, 153, 153, 153);
				$text = strtoupper($ext);
				$font = craft()->path->getAppPath().'etc/assets/helveticaneue-webfont.ttf';

				// Get the bounding box so we can calculate the position
				$box = imagettfbbox($sourceSize['extSize'], 0, $font, $text);
				$width = $box[4] - $box[0];

				// place the text in the center-bottom-ish of the image
				imagettftext($image, $sourceSize['extSize'], 0, ceil(($sourceSize['size'] - $width) / 2), $sourceSize['extY'], $color, $font, $text);
			}

			// Preserve transparency
			imagealphablending($image, false);
			$color = imagecolorallocatealpha($image, 0, 0, 0, 127);
			imagefill($image, 0, 0, $color);
			imagesavealpha($image, true);

			// Make sure we have a folder to save to and save it.
			IOHelper::ensureFolderExists($sourceFolder);
			imagepng($image, $sourceIconLocation);
		}

		if ($size != $sourceSize['size'])
		{
			// Resize the source icon to fit this size.
			craft()->images->loadImage($sourceIconLocation)
				->scaleAndCrop($size, $size)
				->saveAs($iconLocation);
		}

		return $iconLocation;
	}
}
