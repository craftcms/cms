<?php
namespace Craft;

/**
 * Class ResourcesService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class ResourcesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	public $dateParam;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the cached file system path for a given resource, if we have it.
	 *
	 * @param string $path
	 *
	 * @return string|null
	 */
	public function getCachedResourcePath($path)
	{
		$realPath = craft()->cache->get('resourcePath:'.$path);

		if ($realPath && IOHelper::fileExists($realPath))
		{
			return $realPath;
		}
	}

	/**
	 * Caches a file system path for a given resource.
	 *
	 * @param string $path
	 * @param string $realPath
	 *
	 * @return null
	 */
	public function cacheResourcePath($path, $realPath)
	{
		if (!$realPath)
		{
			$realPath = ':(';
		}

		craft()->cache->set('resourcePath:'.$path, $realPath, null, new AppPathCacheDependency());
	}

	/**
	 * Resolves a resource path to the actual file system path, or returns false if the resource cannot be found.
	 *
	 * @param string $path
	 *
	 * @throws HttpException
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
					// unless js/uncompressed/* is requested, in which case drop the uncompressed/ seg
					if (isset($segs[1]) && $segs[1] == 'uncompressed')
					{
						array_splice($segs, 1, 1);
					}
					else if (craft()->config->get('useCompressedJs'))
					{
						array_splice($segs, 1, 0, 'compressed');
					}

					$path = implode('/', $segs);

					// Make sure we're contained.
					if (PathHelper::ensurePathIsContained($path) === false)
					{
						throw new HttpException(404);
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

						$size = AssetsHelper::cleanAssetName($segs[2], false, true);
						// Looking for either a numeric size or "original" keyword
						if (!is_numeric($size) && $size != "original")
						{
							return false;
						}

						$username = AssetsHelper::cleanAssetName($segs[1], false, true);
						$filename = AssetsHelper::cleanAssetName($segs[3], true, true);

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

							if (IOHelper::isWritable($sizedPhotoFolder))
							{
								craft()->images->loadImage($originalPhotoPath)
									->resize($size)
									->saveAs($sizedPhotoPath);
							}
							else
							{
								Craft::log('Tried to write to target folder and could not: '.$sizedPhotoFolder, LogLevel::Error);
							}
						}

						return $sizedPhotoPath;
					}
				}

				case 'defaultuserphoto':
				{
					return craft()->path->getResourcesPath().'images/user.svg';
				}

				case 'tempuploads':
				{
					array_shift($segs);

					return craft()->path->getTempUploadsPath().implode('/', $segs);
				}

				case 'tempassets':
				{
					array_shift($segs);

					return craft()->path->getAssetsTempSourcePath().implode('/', $segs);
				}

				case 'assetthumbs':
				{
					// Only service asset thumbs for logged-in Control Panel requests.
					if (!craft()->request->isCpRequest())
					{
						// Missing status code.
						throw new HttpException(404);
					}

					if (!craft()->userSession->isLoggedIn())
					{
						// Unauthorized status code.
						throw new HttpException(401);
					}

					if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[1]) || !is_numeric($segs[2]))
					{
						return $this->_getBrokenImageThumbPath();
					}

					$fileModel = craft()->assets->getFileById($segs[1]);

					if (empty($fileModel))
					{
						return $this->_getBrokenImageThumbPath();
					}

					$size = $segs[2];

					try
					{
						return craft()->assetTransforms->getThumbServerPath($fileModel, $size);
					}
					catch (\Exception $e)
					{
						return $this->_getBrokenImageThumbPath();
					}
				}

				case 'icons':
				{
					if (empty($segs[1]) || !preg_match('/^\w+/i', $segs[1]))
					{
						return false;
					}

					return $this->_getIconPath($segs[1]);
				}

				case 'rebrand':
				{
					if (!in_array($segs[1], array('logo', 'icon')))
					{
						return false;
					}

					return craft()->path->getRebrandPath().$segs[1]."/".$segs[2];
				}

				case 'transforms':
				{
					try
					{
						if (!empty($segs[1]))
						{
							$transformIndexModel = craft()->assetTransforms->getTransformIndexModelById((int) $segs[1]);
						}

						if (empty($transformIndexModel))
						{
							throw new HttpException(404);
						}

						$url = craft()->assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);
					}
					catch (Exception $exception)
					{
						throw new HttpException(404, $exception->getMessage());
					}
					craft()->request->redirect($url, true, 302);
					craft()->end();
				}

				case '404':
				{
					throw new HttpException(404);
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
		craft()->plugins->loadPlugins();

		$pluginPath = craft()->plugins->callFirst('getResourcePath', array($path), true);

		if ($pluginPath && IOHelper::fileExists($pluginPath))
		{
			return $pluginPath;
		}

		// Couldn't find the file
		return false;
	}

	/**
	 * Sends a resource back to the browser.
	 *
	 * @param string $path
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function sendResource($path)
	{
		if (PathHelper::ensurePathIsContained($path) === false)
		{
			throw new HttpException(404);
		}

		$cachedPath = $this->getCachedResourcePath($path);

		if ($cachedPath)
		{
			if ($cachedPath == ':(')
			{
				// 404
				$realPath = false;
			}
			else
			{
				// We've got it already
				$realPath = $cachedPath;
			}
		}
		else
		{
			// We don't have a cache of the file system path, so let's get it
			$realPath = $this->getResourcePath($path);

			// Now cache it
			$this->cacheResourcePath($path, $realPath);
		}

		if ($realPath === false || !IOHelper::fileExists($realPath))
		{
			throw new HttpException(404);
		}

		// If there is a timestamp and HTTP_IF_MODIFIED_SINCE exists, check the timestamp against requested file's last
		// modified date. If the last modified date is less than the timestamp, return a 304 not modified and let the
		// browser serve it from cache.
		$timestamp = craft()->request->getParam($this->dateParam, null);

		if ($timestamp !== null && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER))
		{
			$requestDate = DateTime::createFromFormat('U', $timestamp);
			$lastModifiedFileDate = IOHelper::getLastTimeModified($realPath);

			if ($lastModifiedFileDate && $lastModifiedFileDate <= $requestDate)
			{
				// Let the browser serve it from cache.
				HeaderHelper::setHeader('HTTP/1.1 304 Not Modified');
				craft()->end();
			}
		}

		// Note that $content may be empty -- they could be requesting a blank text file or something. It doens't matter.
		// No need to throw a 404.
		$content = IOHelper::getFileContents($realPath);

		// Normalize URLs in CSS files
		$mimeType = IOHelper::getMimeTypeByExtension($realPath);

		if (mb_strpos($mimeType, 'css') !== false)
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

			craft()->request->sendFile($realPath, $content, $options);
		}
		else
		{
			craft()->request->xSendFile($realPath);
		}

		// You shall not pass.
		craft()->end();
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param $match
	 *
	 * @return string
	 */
	private function _normalizeCssUrl($match)
	{
		// Ignore root-relative, absolute, and data: URLs
		if (preg_match('/^(\/|https?:\/\/|data:)/', $match[3]))
		{
			return $match[0];
		}

		// Clean up any relative folders at the beginning of the CSS URL
		$requestFolder = IOHelper::getFolderName(craft()->request->getPath());
		$requestFolderParts = array_filter(explode('/', $requestFolder));
		$cssUrlParts = array_filter(explode('/', $match[3]));

		while (isset($cssUrlParts[0]) && $cssUrlParts[0] == '..' && $requestFolderParts)
		{
			array_pop($requestFolderParts);
			array_shift($cssUrlParts);
		}

		$pathParts = array_merge($requestFolderParts, $cssUrlParts);
		$path = implode('/', $pathParts);
		$url = UrlHelper::getUrl($path);

		// Is this going to be a resource URL?
		$rootResourceUrl = UrlHelper::getUrl(craft()->config->getResourceTrigger()).'/';
		$rootResourceUrlLength = strlen($rootResourceUrl);

		if (strncmp($rootResourceUrl, $url, $rootResourceUrlLength) === 0)
		{
			// Isolate the relative resource path
			$resourcePath = substr($url, $rootResourceUrlLength);

			// Give UrlHelper a chance to add the timestamp
			$url = UrlHelper::getResourceUrl($resourcePath);
		}

		// Return the normalized CSS URL declaration
		return $match[1].$url.$match[4];
	}

	/**
	 * Get icon path for a given extension
	 *
	 * @param $ext
	 *
	 * @return string
	 */
	private function _getIconPath($ext)
	{
		$sourceIconPath = craft()->path->getResourcesPath().'images/file.svg';
		$extLength = mb_strlen($ext);

		if ($extLength > 5)
		{
			// Too long; just use the blank file icon
			return $sourceIconPath;
		}

		// See if the icon already exists
		$iconPath = craft()->path->getAssetsIconsPath().StringHelper::toLowerCase($ext).'.svg';

		if (IOHelper::fileExists($iconPath))
		{
			return $iconPath;
		}

		// Create a new one
		$svgContents = IOHelper::getFileContents($sourceIconPath);
		$textSize = ($extLength <= 3 ? '26' : ($extLength == 4 ? '22' : '18'));
		$textNode = '<text x="50" y="73" text-anchor="middle" font-family="sans-serif" fill="#8F98A3" '.
			'font-size="'.$textSize.'">'.
			StringHelper::toUpperCase($ext).
			'</text>';
		$svgContents = str_replace('<!-- EXT -->', $textNode, $svgContents);
		IOHelper::writeToFile($iconPath, $svgContents);

		return $iconPath;
	}

	/**
	 * Returns the path to the broken image thumbnail.
	 *
	 * @return string
	 */
	private function _getBrokenImageThumbPath()
	{
		//http_response_code(404);
		return craft()->path->getResourcesPath().'images/brokenimage.svg';
	}
}
