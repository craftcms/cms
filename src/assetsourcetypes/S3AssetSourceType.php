<?php
namespace Craft;

Craft::requirePackage(CraftPackage::PublishPro);

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
	 * Init
	 */
	public function init()
	{
		$settings = $this->getSettings();
		$this->_s3 = new \S3($settings->keyId, $settings->secret);
	}

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
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/assetsourcetypes/S3/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Prepare the S3 connection for requests to this bucket.
	 */
	private function _prepareForRequests()
	{
		$settings = $this->getSettings();
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

		$fileList = $this->_s3->getBucket($settings->bucket);

		$fileList = array_filter($fileList, function ($value) {
			$path = $value['name'];
			$segments = explode('/', $path);

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

				if (substr($file['name'], -1) == '/')
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

			$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $uriPath);

			$targetPath = craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);

			$timeModified = new DateTime('@'.$fileInfo['time']);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $timeModified || !IOHelper::fileExists($targetPath))
			{
				$this->_s3->getObject($settings->bucket, $indexEntryModel->uri, $targetPath);
				clearstatcache();
				list ($fileModel->width, $fileModel->height) = getimagesize($targetPath);
			}

			$fileModel->dateModified = new DateTime('@'.$fileInfo['time']);

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

		$uriPath = $folder->fullPath.$fileName;

		$this->_prepareForRequests();
		$settings = $this->getSettings();
		$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $uriPath);

		if ($fileInfo)
		{
			$response = new AssetOperationResponseModel();
			$response->setPrompt($this->_getUserPromptOptions($fileName));
			$response->setDataItem('fileName', $fileName);
			return $response;
		}

		clearstatcache();
		$this->_prepareForRequests();

		if (!$this->_s3->putObject(array('file' => $filePath), $this->getSettings()->bucket, $uriPath, \S3::ACL_PUBLIC_READ))
		{
			throw new Exception(Craft::t('Could not copy file to target destination'));
		}

		$response = new AssetOperationResponseModel();
		$response->setSuccess();
		$response->setDataItem('filePath', $uriPath);
		return $response;
	}

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $fileModel)
	{
		return craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);
	}

	/**
	 * Get the timestamp of when a file transformation was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $transformationHandle
	 * @return mixed
	 */
	public function getTimeTransformationModified(AssetFileModel $fileModel, $transformationHandle)
	{
		$folder = $fileModel->getFolder();
		$path = $folder->fullPath.'_'.$transformationHandle.'/'.$fileModel->filename;
		$this->_prepareForRequests();
		$info = $this->_s3->getObjectInfo($this->getSettings()->bucket, $path);

		if (empty($info))
		{
			return false;
		}

		return new DateTime('@'.$info['time']);
	}

	/**
	* Put an image transformation for the File and handle using the provided path to the source image.
	*
	* @param AssetFileModel $fileModel
	* @param $handle
	* @param $sourceImage
	* @return mixed
	*/
	public function putImageTransformation(AssetFileModel $fileModel, $handle, $sourceImage)
	{
		$this->_prepareForRequests();
		$targetFile = $fileModel->getFolder()->fullPath.'_'.$handle.'/'.$fileModel->filename;

		return $this->_s3->putObject(array('file' => $sourceImage), $this->getSettings()->bucket, $targetFile, \S3::ACL_PUBLIC_READ);
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
		$fileList = $this->_s3->getBucket($this->getSettings()->bucket, $folder->fullPath);

		$fileNameParts = explode(".", $fileName);
		$extension = array_pop($fileNameParts);

		$fileNameStart = join(".", $fileNameParts) . '_';
		$index = 1;

		while ( isset($fileList[$folder->fullPath . $fileNameStart . $index . '.' . $extension]))
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
		$location = AssetsHelper::getTempFilePath();

		$this->_prepareForRequests();
		$this->_s3->getObject($this->getSettings()->bucket, $this->_getS3Path($file), $location);

		return $location;
	}

	/**
	 * Get a file's S3 path.
	 *
	 * @param AssetFileModel $file
	 * @return string
	 */
	private function _getS3Path(AssetFileModel $file)
	{
		$folder = craft()->assets->getFolderById($file->folderId);
		return $folder->fullPath.$file->filename;
	}

	/**
	 * Delete just the source file for an Assets File.
	 *
	 * @param AssetFileModel $file
	 * @return void
	 */
	protected function _deleteSourceFile(AssetFileModel $file)
	{
		$this->_prepareForRequests();
		$this->_s3->deleteObject($this->getSettings()->bucket, $this->_getS3Path($file));
	}

	/**
	 * Delete all the generated image transformations for this file.

	 *
	 * @param AssetFileModel $file
	 */
	protected function _deleteGeneratedImageTransformations(AssetFileModel $file)
	{
		$folder = craft()->assets->getFolderById($file->folderId);
		$transformations = craft()->assetTransformations->getAssetTransformations();
		$this->_prepareForRequests();

		$bucket = $this->getSettings()->bucket;
		$this->_s3->deleteObject($bucket, $this->_getS3Path($file));

		foreach ($transformations as $handle => $transformation)
		{
			$this->_s3->deleteObject($bucket, $folder->fullPath.'_'.$handle.'/'.$file->filename);
		}
	}

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel $file
	 * @param AssetFolderModel $targetFolder
	 * @param string $fileName
	 * @param string $userResponse Conflict resolution response
	 * @return mixed
	 */
	protected function _moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $userResponse = '')
	{
		if (empty($fileName))
		{
			$fileName = $file->filename;
		}

		$newServerPath = $targetFolder->fullPath.$fileName;

		$conflictingRecord = craft()->assets->findFile(array(
			'folderId' => $targetFolder->id,
			'filename' => $fileName
		));

		$this->_prepareForRequests();
		$settings = $this->getSettings();
		$fileInfo = $this->_s3->getObjectInfo($settings->bucket, $newServerPath);

		$conflict = $fileInfo || (!craft()->assets->isMergeInProgress() && is_object($conflictingRecord));

		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			$response->setPrompt($this->_getUserPromptOptions($fileName));
			$response->setDataItem('fileName', $fileName);
			return $response;

		}

		$bucket = $this->getSettings()->bucket;

		if (!$this->_s3->copyObject($bucket, $file->getFolder()->fullPath.$file->filename, $bucket, $newServerPath))
		{
			$response = new AssetOperationResponseModel();
			$response->setError(Craft::t("Could not save the file"));
			return $response;
		}

		$this->_s3->deleteObject($bucket, $this->_getS3Path($file));

		if ($file->kind == 'image')
		{
			$this->_deleteGeneratedThumbnails($file);

			// Move transformations
			$transformations = craft()->assetTransformations->getAssetTransformations();
			$baseFromPath = $file->getFolder()->fullPath;
			$baseToPath = $targetFolder->fullPath;

			foreach ($transformations as $handle => $transformation)
			{
				$this->_s3->copyObject($bucket, $baseFromPath.'_'.$handle.'/'.$file->filename, $bucket, $baseToPath.'_'.$handle.'/'.$fileName);
				$this->_s3->deleteObject($bucket, $baseFromPath.'_'.$handle.'/'.$file->filename);
			}
		}

		$response = new AssetOperationResponseModel();
		$response->setSuccess();
		$response->setDataItem('newId', $file->id);
		$response->setDataItem('newFileName', $fileName);

		return $response;
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
		return (bool) $this->_s3->getObjectInfo($this->getSettings()->bucket, $parentFolder->fullPath.$folderName);

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
		return $this->_s3->putObject('', $this->getSettings()->bucket, rtrim($parentFolder->fullPath.$folderName, '/') . '/', \S3::ACL_PUBLIC_READ);
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

		$newFullPath = $this->_getParentFullPath($folder->fullPath).$newName.'/';

		$this->_prepareForRequests();
		$bucket = $this->getSettings()->bucket;
		$filesToMove = $this->_s3->getBucket($bucket, $folder->fullPath);

		rsort($filesToMove);
		foreach ($filesToMove as $file)
		{
			$filePath = substr($file['name'], strlen($folder->fullPath));

			$this->_s3->copyObject($bucket, $file['name'], $bucket, $newFullPath . $filePath, \S3::ACL_PUBLIC_READ);
			$this->_s3->deleteObject($bucket, $file['name']);
		}

		return TRUE;
	}

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @return boolean
	 */
	protected function _deleteSourceFolder(AssetFolderModel $folder)
	{
		$this->_prepareForRequests();
		$bucket = $this->getSettings()->bucket;
		$objectsToDelete = $this->_s3->getBucket($bucket, $folder->fullPath);

		foreach ($objectsToDelete as $uri)
		{
			$this->_s3->deleteObject($bucket, $uri['name']);
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


}
