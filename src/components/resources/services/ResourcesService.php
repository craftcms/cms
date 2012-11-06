<?php
namespace Blocks;

/**
 *
 */
class ResourcesService extends BaseApplicationComponent
{
	/**
	 * Resolves a resource path to the actual file system path, or returns false if the resource cannot be found.
	 *
	 * @param string|array $path
	 * @return string|false
	 */
	public function getResourcePath($path)
	{
		if (is_array($path))
		{
			$segs = $path;
			$path = implode('/', $segs);
		}
		else
		{
			$segs = explode('/', $path);
		}

		// Special resource routing
		if (isset($segs[0]))
		{
			switch($segs[0])
			{
				case 'js':
				{
					// Route to js/compressed/ if useCompressedJs is enabled
					if (blx()->config->useCompressedJs)
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

						return blx()->path->getTempPath().'userphotos/'.$segs[2].'/'.$segs[3];
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

						$userPhotosPath = blx()->path->getUserPhotosPath().$username.'/';
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

							blx()->images->loadImage($originalPhotoPath)
								->resizeTo($size)
								->saveAs($sizedPhotoPath);
						}

						return $sizedPhotoPath;
					}
				}
			}
		}

		// Check app/resources folder first.
		$appResourcePath = blx()->path->getResourcesPath().$path;

		if (IOHelper::fileExists($appResourcePath))
		{
			return $appResourcePath;
		}

		// See if the first segment is a plugin handle.
		if (isset($segs[0]))
		{
			$pluginResourcePath = blx()->path->getPluginsPath().$segs[0].'/'.'resources/'.implode('/', array_splice($segs, 1));

			if (IOHelper::fileExists($pluginResourcePath))
			{
				return $pluginResourcePath;
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

		// If we're using query string URLs, the browser won't know how to resolve url(../resource.ext) in CSS files,
		// so let's change them to absolute URLs.
		if (blx()->request->getUrlFormat() == UrlFormat::QueryString)
		{
			$mimeType = IOHelper::getMimeTypeByExtension($path);

			if (strpos($mimeType, 'css') > 0)
			{
				$content = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', array(&$this, '_convertRelativeUrlMatch'), $content);
			}
		}

		if (!blx()->config->useXSendFile)
		{
			blx()->request->sendFile(IOHelper::getFileName($path), $content, array('forceDownload' => false));
		}
		else
		{
			blx()->request->xSendFile($path);
		}

		exit(1);
	}

	/**
	 * @access private
	 * @param $match
	 * @return string
	 */
	private function _convertRelativeUrlMatch($match)
	{
		// ignore root-relative, absolute, and data: URLs
		if (preg_match('/^(\/|https?:\/\/|data:)/', $match[3]))
		{
			return $match[0];
		}

		return $match[1].IOHelper::getFolderName(blx()->request->getUrl()).'/'.$match[3].$match[4];
	}
}
