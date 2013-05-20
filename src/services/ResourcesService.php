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
					if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[1]) || !preg_match('/^(?P<width>[0-9]+)x(?P<height>[0-9]+)/', $segs[2]))
					{
						return false;
					}

					$fileModel = craft()->assets->getFileById($segs[1]);
					if (empty($fileModel))
					{
						return false;
					}

					$sourceType = craft()->assetSources->getSourceTypeById($fileModel->sourceId);

					$size = IOHelper::cleanFilename($segs[2]);
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
							->scaleToFit($size)
							->saveAs($thumbPath);
					}

					return $thumbPath;
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

		if (!$content)
		{
			throw new HttpException(404);
		}

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
}
