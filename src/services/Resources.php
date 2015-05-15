<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateTime;
use craft\app\errors\Exception;
use craft\app\errors\HttpException;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\HeaderHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\PathHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use yii\base\Component;
use yii\helpers\FileHelper;

/**
 * Class Resources service.
 *
 * An instance of the Resources service is globally accessible in Craft via [[Application::resources `Craft::$app->getResources()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Resources extends Component
{
	// Constants
	// =========================================================================

	const DefaultUserphotoFilename = 'user.gif';

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
		$realPath = Craft::$app->getCache()->get('resourcePath:'.$path);

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

		Craft::$app->getCache()->set('resourcePath:'.$path, $realPath);
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
				case 'userphotos':
				{
					if (isset($segs[1]) && $segs[1] == 'temp')
					{
						if (!isset($segs[2]))
						{
							return false;
						}

						return Craft::$app->getPath()->getTempUploadsPath().'/userphotos/'.$segs[2].'/'.$segs[3];
					}
					else
					{
						if (!isset($segs[3]))
						{
							return false;
						}

						$size = AssetsHelper::cleanAssetName($segs[2], false);
						// Looking for either a numeric size or "original" keyword
						if (!is_numeric($size) && $size != "original")
						{
							return false;
						}

						$username = AssetsHelper::cleanAssetName($segs[1], false);
						$filename = AssetsHelper::cleanAssetName($segs[3]);

						$userPhotosPath = Craft::$app->getPath()->getUserPhotosPath().'/'.$username;
						$sizedPhotoFolder = $userPhotosPath.'/'.$size;
						$sizedPhotoPath = $sizedPhotoFolder.'/'.$filename;

						// If the photo doesn't exist at this size, create it.
						if (!IOHelper::fileExists($sizedPhotoPath))
						{
							$originalPhotoPath = $userPhotosPath.'/original/'.$filename;

							if (!IOHelper::fileExists($originalPhotoPath))
							{
								return false;
							}

							IOHelper::ensureFolderExists($sizedPhotoFolder);

							if (IOHelper::isWritable($sizedPhotoFolder))
							{
								Craft::$app->getImages()->loadImage($originalPhotoPath)
									->resize($size)
									->saveAs($sizedPhotoPath);
							}
							else
							{
								Craft::error('Tried to write to target folder and could not: '.$sizedPhotoFolder, __METHOD__);
							}
						}

						return $sizedPhotoPath;
					}
				}

				case 'defaultuserphoto':
				{
					if (!isset($segs[1]) || !is_numeric($segs[1]))
					{
						return;
					}

					$size = $segs[1];
					$sourceFile = Craft::$app->getPath()->getResourcesPath().'/images/'.static::DefaultUserphotoFilename;
					$targetFolder = Craft::$app->getPath()->getUserPhotosPath().'/__default__';
					IOHelper::ensureFolderExists($targetFolder);

					if (IOHelper::isWritable($targetFolder))
					{
						$targetFile = $targetFolder.'/'.$size.'.'.IOHelper::getExtension($sourceFile);
						Craft::$app->getImages()->loadImage($sourceFile)
							->resize($size)
							->saveAs($targetFile);

						return $targetFile;
					}
					else
					{
						Craft::error('Tried to write to the target folder, but could not:'.$targetFolder, __METHOD__);
					}
				}

				case 'tempuploads':
				{
					array_shift($segs);

					return Craft::$app->getPath()->getTempUploadsPath().'/'.implode('/', $segs);
				}

				case 'tempassets':
				{
					array_shift($segs);

					return Craft::$app->getPath()->getAssetsTempSourcePath().'/'.implode('/', $segs);
				}

				case 'assetthumbs':
				{
					if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[1]) || !is_numeric($segs[2]))
					{
						return false;
					}

					$fileModel = Craft::$app->getAssets()->getFileById($segs[1]);
					if (empty($fileModel))
					{
						return false;
					}

					$size = $segs[2];

					// Make sure plugins are loaded in case the asset lives in a plugin-supplied volume type
					Craft::$app->getPlugins()->loadPlugins();

					return Craft::$app->getAssetTransforms()->getThumbServerPath($fileModel, $size);
				}

				case 'icons':
				{
					if (empty($segs[1]) || empty($segs[2]) || !is_numeric($segs[2]) || !preg_match('/^(?P<extension>[a-z_0-9]+)/i', $segs[1]))
					{
						return false;
					}

					$ext = StringHelper::toLowerCase($segs[1]);
					$size = $segs[2];

					$iconPath = $this->_getIconPath($ext, $size);

					return $iconPath;
				}

				case 'logo':
				{
					return Craft::$app->getPath()->getStoragePath().'/'.implode('/', $segs);
				}

				case 'transforms':
				{
					// Make sure plugins are loaded in case the asset lives in a plugin-supplied volume type
					Craft::$app->getPlugins()->loadPlugins();

					try
					{
						if (!empty($segs[1]))
						{
							$transformIndexModel = Craft::$app->getAssetTransforms()->getTransformIndexModelById((int) $segs[1]);
						}

						if (empty($transformIndexModel))
						{
							throw new HttpException(404);
						}

						$url = Craft::$app->getAssetTransforms()->ensureTransformUrlByIndexModel($transformIndexModel);
					}
					catch (Exception $exception)
					{
						throw new HttpException(404, $exception->getMessage());
					}

					Craft::$app->getResponse()->redirect($url);
					Craft::$app->end();
				}
			}
		}

		// Check app/resources folder first.
		$appResourcePath = Craft::$app->getPath()->getResourcesPath().'/'.$path;

		if (IOHelper::fileExists($appResourcePath))
		{
			return $appResourcePath;
		}

		// See if the first segment is a plugin handle.
		if (isset($segs[0]))
		{
			$pluginResourcePath = Craft::$app->getPath()->getPluginsPath().'/'.$segs[0].'/'.'resources/'.implode('/', array_splice($segs, 1));

			if (IOHelper::fileExists($pluginResourcePath))
			{
				return $pluginResourcePath;
			}
		}

		// Maybe a plugin wants to do something custom with this URL
		$pluginPath = Craft::$app->getPlugins()->callFirst('getResourcePath', [$path], true);

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
		$timestamp = Craft::$app->getRequest()->getParam($this->dateParam, null);

		if ($timestamp !== null && array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER))
		{
			$requestDate = DateTime::createFromFormat('U', $timestamp);
			$lastModifiedFileDate = IOHelper::getLastTimeModified($realPath);

			if ($lastModifiedFileDate && $lastModifiedFileDate <= $requestDate)
			{
				// Let the browser serve it from cache.
				Craft::$app->getResponse()->setStatusCode(304);
				Craft::$app->end();
			}
		}

		$filename = IOHelper::getFilename($realPath);
		$mimeType = FileHelper::getMimeTypeByExtension($realPath);
		$response = Craft::$app->getResponse();

		$options = [
			'mimeType' => $mimeType,
			'inline' => true,
		];

		if (Craft::$app->getRequest()->getQueryParam($this->dateParam))
		{
			$response->setCacheHeaders();
			$response->setLastModifiedHeader($realPath);
		}

		// Is this a CSS file?
		if ($mimeType == 'text/css')
		{
			// Normalize the URLs
			$contents = IOHelper::getFileContents($realPath);
			$contents = preg_replace_callback('/(url\(([\'"]?))(.+?)(\2\))/', [&$this, '_normalizeCssUrl'], $contents);

			$response->sendContentAsFile($contents, $filename, $options);
		}
		else
		{
			$response->sendFile($realPath, $filename, $options);
		}

		// You shall not pass.
		Craft::$app->end();
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
		$requestFolder = IOHelper::getFolderName(Craft::$app->getRequest()->getPathInfo());
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
		$rootResourceUrl = UrlHelper::getUrl(Craft::$app->getConfig()->getResourceTrigger()).'/';
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
	 * Get icon path for an extension and size
	 *
	 * @param $ext
	 * @param $size
	 *
	 * @return string
	 */
	private function _getIconPath($ext, $size)
	{
		if (mb_strlen($ext) > 4)
		{
			$ext = '';
		}

		$extAlias = [
			'docx' => 'doc',
			'xlsx' => 'xls',
			'pptx' => 'ppt',
			'jpeg' => 'jpg',
			'html' => 'htm',
		];

		if (isset($extAlias[$ext]))
		{
			$ext = $extAlias[$ext];
		}

		$sizeFolder = Craft::$app->getPath()->getAssetsIconsPath().'/'.$size;

		// See if we have the icon already
		$iconLocation = $sizeFolder.'/'.$ext.'.png';

		if (IOHelper::fileExists($iconLocation))
		{
			return $iconLocation;
		}

		// We are going to need that folder to exist.
		IOHelper::ensureFolderExists($sizeFolder);

		// Determine the closest source size
		$sourceSizes = [
			['size' => 40,  'extSize' => 7,  'extY' => 32],
			['size' => 350, 'extSize' => 60, 'extY' => 280],
		];

		foreach ($sourceSizes as $sourceSize)
		{
			if ($sourceSize['size'] >= $size)
			{
				break;
			}
		}

		$sourceFolder = Craft::$app->getPath()->getAssetsIconsPath().'/'.$sourceSize['size'];

		// Do we have a source icon that we can resize?
		$sourceIconLocation = $sourceFolder.'/'.$ext.'.png';

		if (!IOHelper::fileExists($sourceIconLocation))
		{
			$sourceFile = Craft::$app->getPath()->getAppPath().'/resources/images/fileicons/'.$sourceSize['size'].'.png';
			$image = \imagecreatefrompng($sourceFile);

			// Text placement.
			if ($ext)
			{
				$color = \imagecolorallocate($image, 153, 153, 153);
				$text = StringHelper::toUpperCase($ext);
				$font = Craft::$app->getPath()->getResourcesPath().'/fonts/helveticaneue-webfont.ttf';

				// Get the bounding box so we can calculate the position
				$box = \imagettfbbox($sourceSize['extSize'], 0, $font, $text);
				$width = $box[4] - $box[0];

				// place the text in the center-bottom-ish of the image
				\imagettftext($image, $sourceSize['extSize'], 0, ceil(($sourceSize['size'] - $width) / 2), $sourceSize['extY'], $color, $font, $text);
			}

			// Preserve transparency
			\imagealphablending($image, false);
			$color = \imagecolorallocatealpha($image, 0, 0, 0, 127);
			\imagefill($image, 0, 0, $color);
			\imagesavealpha($image, true);

			// Make sure we have a folder to save to and save it.
			IOHelper::ensureFolderExists($sourceFolder);
			\imagepng($image, $sourceIconLocation);
		}

		if ($size != $sourceSize['size'])
		{
			// Resize the source icon to fit this size.
			Craft::$app->getImages()->loadImage($sourceIconLocation)
				->scaleAndCrop($size, $size)
				->saveAs($iconLocation);
		}

		return $iconLocation;
	}
}
