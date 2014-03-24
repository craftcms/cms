<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * S3 source type class
 */
class S3AssetSourceType extends BaseAssetSourceType
{
	/**
	 * A list of predefined endpoints.
	 *
	 * @var array
	 */
	private static $_predefinedEndpoints = array(
		'US' => 's3.amazonaws.com',
		'EU' => 's3-eu-west-1.amazonaws.com'
	);

	/**
	 * @var \S3
	 */
	private $_s3;

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'Amazon S3';
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'keyId'      => array(AttributeType::String, 'required' => true),
			'secret'     => array(AttributeType::String, 'required' => true),
			'bucket'     => array(AttributeType::String, 'required' => true),
			'location'   => array(AttributeType::String, 'required' => true),
			'urlPrefix'  => array(AttributeType::String, 'required' => true),
			'subfolder'  => array(AttributeType::String, 'default' => ''),
			'expires'    => array(AttributeType::String, 'default' => ''),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$settings = $this->getSettings();

		$settings->expires = $this->_extractExpiryInformation($settings->expires);

		return craft()->templates->render('_components/assetsourcetypes/S3/settings', array(
			'settings' => $this->getSettings(),
			'periods' => array_merge(array('' => ''), $this->getPeriodList())
		));
	}

	/**
	 * Prepare the S3 connection for requests to this bucket.
	 *
	 * @param $settings
	 */
	private function _prepareForRequests($settings = null)
	{
		if (is_null($settings))
		{
			$settings = $this->getSettings();
		}

		if (is_null($this->_s3))
		{
			$this->_s3 = new \S3($settings->keyId, $settings->secret);
		}

		\S3::setAuth($settings->keyId, $settings->secret);
		$this->_s3->setEndpoint(static::getEndpointByLocation($settings->location));
	}

	/**
	 * Get bucket list with credentials.
	 *
	 * @param $keyId
	 * @param $secret
	 * @return array
	 * @throws Exception
	 */
	public static function getBucketList($keyId, $secret)
	{
		$s3 = new \S3($keyId, $secret);
		$buckets = @$s3->listBuckets();

		if (empty($buckets))
		{
			throw new Exception(Craft::t("Credentials rejected by target host."));
		}

		$bucketList = array();

		foreach ($buckets as $bucket)
		{
			$location = $s3->getBucketLocation($bucket);

			$bucketList[] = array(
				'bucket' => $bucket,
				'location' => $location,
				'url_prefix' => 'http://'.static::getEndpointByLocation($location).'/'.$bucket.'/'
			);

		}

		return $bucketList;
	}

	/**
	 * Get a bucket's endpoint by location.
	 *
	 * @param $location
	 * @return string
	 */
	public static function getEndpointByLocation($location)
	{
		if (isset(static::$_predefinedEndpoints[$location]))
		{
			return static::$_predefinedEndpoints[$location];
		}

		return 's3-'.$location.'.amazonaws.com';
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		$settings = $this->getSettings();
		$this->_prepareForRequests();

		$offset = 0;
		$total = 0;

		$prefix = $this->_getPathPrefix();
		$fileList = $this->_s3->getBucket($settings->bucket, $prefix);

		$fileList = array_filter($fileList, function($value)
		{
			$path = $value['name'];

			$segments = explode('/', $path);
			// Ignore the file
			array_pop($segments);

			foreach ($segments as $segment)
			{
				if (isset($segment[0]) && $segment[0] == '_')
				{
					return false;
				}
			}

			return true;
		});

		$bucketFolders = array();

		foreach ($fileList as $file)
		{
			// Strip the prefix, so we don't index the parent folders
			$file['name'] = mb_substr($file['name'], mb_strlen($prefix));

			if (!preg_match(AssetsHelper::IndexSkipItemsPattern, $file['name']))
			{
				// In S3, it's possible to have files in folders that don't exist. E.g. - one/two/three.jpg.
				// If folder "one" is empty, except for folder "two", then "one" won't show up in this list so we work around it.

				// Matches all paths with folders, except if folder is last or no folder at all.
				if (preg_match('/(.*\/).+$/', $file['name'], $matches))
				{
					$folders = explode('/', rtrim($matches[1], '/'));
					$basePath = '';

					foreach ($folders as $folder)
					{
						$basePath .= $folder .'/';

						// This is exactly the case referred to above
						if ( ! isset($bucketFolders[$basePath]))
						{
							$bucketFolders[$basePath] = true;
						}
					}
				}

				if (mb_substr($file['name'], -1) == '/')
				{
					$bucketFolders[$file['name']] = true;
				}
				else
				{
					$indexEntry = array(
						'sourceId' => $this->model->id,
						'sessionId' => $sessionId,
						'offset' => $offset++,
						'uri' => $file['name'],
						'size' => $file['size']
					);

					craft()->assetIndexing->storeIndexEntry($indexEntry);
					$total++;
				}
			}
		}

		$indexedFolderIds = array();
		$indexedFolderIds[craft()->assetIndexing->ensureTopFolder($this->model)] = true;

		// Ensure folders are in the DB
		foreach ($bucketFolders as $fullPath => $nothing)
		{
			$folderId = $this->_ensureFolderByFulPath($fullPath);
			$indexedFolderIds[$folderId] = true;
		}

		$missingFolders = $this->_getMissingFolders($indexedFolderIds);

		return array('sourceId' => $this->model->id, 'total' => $total, 'missingFolders' => $missingFolders);
	}

	/**
	 * Process an indexing session.
	 *
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 */
	public function processIndex($sessionId, $offset)
	{
		$indexEntryModel = craft()->assetIndexing->getIndexEntry($this->model->id, $sessionId, $offset);

		if (empty($indexEntryModel))
		{
			return false;
		}

		$uriPath = $indexEntryModel->uri;
		$fileModel = $this->_indexFile($uriPath);
		$this->_prepareForRequests();

		if ($fileModel)
		{
			$settings = $this->getSettings();

			craft()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;

			$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $this->_getPathPrefix().$uriPath);

			$targetPath = craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.IOHelper::getExtension($fileModel->filename);

			$timeModified = new DateTime('@'.$fileInfo['time']);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $timeModified || !IOHelper::fileExists($targetPath))
			{
				$this->_s3->getObject($settings->bucket, $this->_getPathPrefix().$indexEntryModel->uri, $targetPath);
				clearstatcache();
				list ($fileModel->width, $fileModel->height) = getimagesize($targetPath);
			}

			$fileModel->dateModified = $timeModified;

			craft()->assets->storeFile($fileModel);

			return $fileModel->id;
		}

		return false;
	}

	/**
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filePath
	 * @param $fileName
	 * @return AssetFileModel
	 * @throws Exception
	 */
	protected function _insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName)
	{
		$fileName = IOHelper::cleanFilename($fileName);
		$extension = IOHelper::getExtension($fileName);

		if (! IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Craft::t('This file type is not allowed'));
		}

		$uriPath = $this->_getPathPrefix().$folder->path.$fileName;

		$this->_prepareForRequests();
		$settings = $this->getSettings();
		$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $uriPath);

		if ($fileInfo)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->_getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		clearstatcache();
		$this->_prepareForRequests();

		if (!$this->_putObject($filePath, $this->getSettings()->bucket, $uriPath, \S3::ACL_PUBLIC_READ))
		{
			throw new Exception(Craft::t('Could not copy file to target destination'));
		}

		$response = new AssetOperationResponseModel();
		return $response->setSuccess()->setDataItem('filePath', $uriPath);
	}

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $fileModel)
	{
		return craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.IOHelper::getExtension($fileModel->filename);
	}

	/**
	 * Get the timestamp of when a file transform was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $transformLocation
	 * @return mixed
	 */
	public function getTimeTransformModified(AssetFileModel $fileModel, $transformLocation)
	{
		$folder = $fileModel->getFolder();
		$path = $this->_getPathPrefix().$folder->path.$transformLocation.'/'.$fileModel->filename;
		$this->_prepareForRequests();
		$info = $this->_s3->getObjectInfo($this->getSettings()->bucket, $path);

		if (empty($info))
		{
			return false;
		}

		return new DateTime('@'.$info['time']);
	}

	/**
	* Put an image transform for the File and handle using the provided path to the source image.
	*
	* @param AssetFileModel $fileModel
	* @param $handle
	* @param $sourceImage
	* @return mixed
	*/
	public function putImageTransform(AssetFileModel $fileModel, $handle, $sourceImage)
	{
		$this->_prepareForRequests();
		$targetFile = $this->_getPathPrefix().$fileModel->getFolder()->path.'_'.ltrim($handle, '_').'/'.$fileModel->filename;

		return $this->_putObject($sourceImage, $this->getSettings()->bucket, $targetFile, \S3::ACL_PUBLIC_READ);
	}

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @return mixed
	 */
	protected function _getNameReplacement(AssetFolderModel $folder, $fileName)
	{
		$this->_prepareForRequests();
		$fileList = $this->_s3->getBucket($this->getSettings()->bucket, $this->_getPathPrefix().$folder->path);

		// Double-check
		if (!isset($fileList[$this->_getPathPrefix().$folder->path . $fileName]))
		{
			return $fileName;
		}

		$fileNameParts = explode(".", $fileName);
		$extension = array_pop($fileNameParts);

		$fileNameStart = join(".", $fileNameParts) . '_';
		$index = 1;

		while ( isset($fileList[$this->_getPathPrefix().$folder->path . $fileNameStart . $index . '.' . $extension]))
		{
			$index++;
		}

		return $fileNameStart . $index . '.' . $extension;
	}

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param AssetFileModel $file
	 * @return mixed
	 */

	public function getLocalCopy(AssetFileModel $file)
	{
		$location = AssetsHelper::getTempFilePath($file->getExtension());

		$this->_prepareForRequests();
		$this->_s3->getObject($this->getSettings()->bucket, $this->_getS3Path($file), $location);

		return $location;
	}

	/**
	 * Get a file's S3 path.
	 *
	 * @param AssetFileModel $file
	 * @param $settings source settings to use
	 * @return string
	 */
	private function _getS3Path(AssetFileModel $file, $settings = null)
	{
		$folder = $file->getFolder();
		return $this->_getPathPrefix($settings).$folder->path.$file->filename;
	}

	/**
	 * Delete just the source file for an Assets File.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filename
	 * @return void
	 */
	protected function _deleteSourceFile(AssetFolderModel $folder, $filename)
	{
		$this->_prepareForRequests();
		@$this->_s3->deleteObject($this->getSettings()->bucket, $this->_getPathPrefix().$folder->path.$filename);
	}

	/**
	 * Delete all the generated image transforms for this file.
	 *
	 * @param AssetFileModel $file
	 * @return void
	 */
	protected function _deleteGeneratedImageTransforms(AssetFileModel $file)
	{
		$folder = craft()->assets->getFolderById($file->folderId);
		$transforms = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);
		$this->_prepareForRequests();

		$bucket = $this->getSettings()->bucket;

		foreach ($transforms as $location)
		{
			@$this->_s3->deleteObject($bucket, $this->_getPathPrefix().$folder->path.$location.'/'.$file->filename);
		}
	}

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel $file
	 * @param AssetFolderModel $targetFolder
	 * @param string $fileName
	 * @param bool $overwrite if True, will overwrite target destination
	 * @return mixed
	 */
	protected function _moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $overwrite = false)
	{
		if (empty($fileName))
		{
			$fileName = $file->filename;
		}

		$newServerPath = $this->_getPathPrefix().$targetFolder->path.$fileName;

		$conflictingRecord = craft()->assets->findFile(array(
			'folderId' => $targetFolder->id,
			'filename' => $fileName
		));

		$this->_prepareForRequests();
		$settings = $this->getSettings();
		$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $newServerPath);

		$conflict = !$overwrite && ($fileInfo || (!craft()->assets->isMergeInProgress() && is_object($conflictingRecord)));

		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->_getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}


		$bucket = $this->getSettings()->bucket;

		// Just in case we're moving from another bucket with the same access credentials.
		$originatingSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$originatingSettings = $originatingSourceType->getSettings();
		$sourceBucket = $originatingSettings->bucket;

		$this->_prepareForRequests($originatingSettings);

		if (!$this->_s3->copyObject($sourceBucket, $this->_getPathPrefix($originatingSettings).$file->getFolder()->path.$file->filename, $bucket, $newServerPath, \S3::ACL_PUBLIC_READ))
		{
			$response = new AssetOperationResponseModel();
			return $response->setError(Craft::t("Could not save the file"));
		}

		@$this->_s3->deleteObject($sourceBucket, $this->_getS3Path($file, $originatingSettings));

		if ($file->kind == 'image')
		{
			$this->_deleteGeneratedThumbnails($file);

			// Move transforms
			$transforms = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);

			$baseFromPath = $this->_getPathPrefix().$file->getFolder()->path;
			$baseToPath = $this->_getPathPrefix().$targetFolder->path;

			foreach ($transforms as $location)
			{
				// Surpress errors when trying to move image transforms. Maybe the user hasn't updated them yet.
				$copyResult = @$this->_s3->copyObject($sourceBucket, $baseFromPath.$location.'/'.$file->filename, $bucket, $baseToPath.$location.'/'.$fileName, \S3::ACL_PUBLIC_READ);

				// If we failed to copy, that's because source wasn't there. Skip delete and save time - everyone's a winner!
				if ($copyResult)
				{
					@$this->_s3->deleteObject($sourceBucket, $baseFromPath.$location.'/'.$file->filename);
				}
			}
		}

		$response = new AssetOperationResponseModel();
		return $response->setSuccess()
				->setDataItem('newId', $file->id)
				->setDataItem('newFileName', $fileName);
	}

	/**
	 * Return TRUE if a physical folder exists.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param $folderName
	 * @return boolean
	 */
	protected function _sourceFolderExists(AssetFolderModel $parentFolder, $folderName)
	{
		$this->_prepareForRequests();
		return (bool) $this->_s3->getObjectInfo($this->getSettings()->bucket, $this->_getPathPrefix().$parentFolder->path.$folderName);

	}

	/**
	 * Create a physical folder, return TRUE on success.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param $folderName
	 * @return boolean
	 */
	protected function _createSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$this->_prepareForRequests();
		return $this->_putObject('', $this->getSettings()->bucket, $this->_getPathPrefix().rtrim($parentFolder->path.$folderName, '/') . '/', \S3::ACL_PUBLIC_READ);
	}

	/**
	 * Rename a source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $newName
	 * @return boolean
	 */
	protected function _renameSourceFolder(AssetFolderModel $folder, $newName)
	{
		$newFullPath = $this->_getPathPrefix().$this->_getParentFullPath($folder->path).$newName.'/';

		$this->_prepareForRequests();
		$bucket = $this->getSettings()->bucket;
		$filesToMove = $this->_s3->getBucket($bucket, $this->_getPathPrefix().$folder->path);

		rsort($filesToMove);
		foreach ($filesToMove as $file)
		{
			$filePath = mb_substr($file['name'], mb_strlen($this->_getPathPrefix().$folder->path));

			$this->_s3->copyObject($bucket, $file['name'], $bucket, $newFullPath . $filePath, \S3::ACL_PUBLIC_READ);
			@$this->_s3->deleteObject($bucket, $file['name']);
		}

		return TRUE;
	}

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param                  $folderName
	 * @return boolean
	 */
	protected function _deleteSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$this->_prepareForRequests();
		$bucket = $this->getSettings()->bucket;
		$objectsToDelete = $this->_s3->getBucket($bucket, $this->_getPathPrefix().$parentFolder->path.$folderName);

		foreach ($objectsToDelete as $uri)
		{
			@$this->_s3->deleteObject($bucket, $uri['name']);
		}

		return true;
	}

	/**
	 * Determines if a file can be moved internally from original source.
	 *
	 * @param BaseAssetSourceType $originalSource
	 * @return mixed
	 */
	protected function canMoveFileFrom(BaseAssetSourceType $originalSource)
	{
		if ($this->model->type == $originalSource->model->type)
		{
			$settings = $originalSource->getSettings();
			$theseSettings = $this->getSettings();
			if ($settings->keyId == $theseSettings->keyId && $settings->secret == $theseSettings->secret)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Copy a transform for a file from source location to target location.
	 *
	 * @param AssetFileModel $file
	 * @param $source
	 * @param $target
	 * @return mixed
	 */
	public function copyTransform(AssetFileModel $file, $source, $target)
	{
		$this->_prepareForRequests();
		$basePath = $this->_getPathPrefix().$file->getFolder()->path;
		$bucket = $this->getSettings()->bucket;
		@$this->_s3->copyObject($bucket, $basePath.$source.'/'.$file->filename, $bucket, $basePath.$target.'/'.$file->filename, \S3::ACL_PUBLIC_READ);
	}

	/**
	 * Return a prefix for S3 path for settings.
	 *
	 * @param object|null $settings to use, if null, will use current settings
	 * @return string
	 */
	private function _getPathPrefix($settings = null)
	{
		if (is_null($settings))
		{
			$settings = $this->getSettings();
		}

		if (!empty($settings->subfolder))
		{
			return rtrim($settings->subfolder, '/').'/';
		}

		return "";
	}

	/**
	 * Return true if a transform exists at the location for a file.
	 *
	 * @param AssetFileModel $file
	 * @param $location
	 * @return mixed
	 */
	public function transformExists(AssetFileModel $file, $location)
	{
		$this->_prepareForRequests();
		return (bool) @$this->_s3->getObjectInfo($this->getSettings()->bucket, $this->_getPathPrefix().$file->getFolder()->path.$location.'/'.$file->filename);
	}

	/**
	 * Return the source's base URL.
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->getSettings()->urlPrefix.$this->_getPathPrefix();
	}

	/**
	 * Return true if the source is a remote source.
	 *
	 * @return bool
	 */
	public function isRemote()
	{
		return true;
	}

	/**
	 * Put an object into an S3 bucket.
	 *
	 * @param $filePath
	 * @param $bucket
	 * @param $uriPath
	 * @param $permissions
	 * @return bool
	 */
	protected function _putObject($filePath, $bucket, $uriPath, $permissions)
	{
		$object = empty($filePath) ? '' : array('file' => $filePath);
		$headers = array();

		if (!empty($object) && !empty($this->getSettings()->expires) && DateTimeHelper::isValidIntervalString($this->getSettings()->expires))
		{
			$expires = new DateTime();
			$now = new DateTime();
			$expires->modify('+' . $this->getSettings()->expires);
			$diff = $expires->format('U') - $now->format('U');
			$headers['Cache-Control'] = 'max-age=' . $diff . ', must-revalidate';
		}

		return $this->_s3->putObject($object, $bucket, $uriPath, $permissions, array(), $headers);
	}
}
