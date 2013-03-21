<?php
namespace Craft;

Craft::requirePackage(CraftPackage::Cloud);

/**
 * Rackspace source type class
 */
class RackspaceAssetSourceType extends BaseAssetSourceType
{

	const RackspaceServiceName = 'cloudFiles';

	/**
	 * @var array All the used rackspace containers.
	 */
	private static $_rackspaceContainers = array();

	/**
	 * @var array All the stored credentials we know.
	 */
	private static $_storedCredentials = array();

	/**
	 * @var DateTime Datetime when the loaded credentials were last updated.
	 */
	private static $_credentialTimestamp = null;

	/**
	 * When shutting down, export all used credentials.
	 */
	public function __destruct()
	{

		// If data has changed, leave.
		if (craft()->systemSettings->getCategoryTimeUpdated('rackspace') > static::$_credentialTimestamp)
		{
			return;
		}

		$currentSettings = array();

		foreach (static::$_rackspaceContainers as $key => $container)
		{
			/** @var \OpenCloud\ObjectStore\Container $container */
			$currentSettings[$key] = $container->Service()->Connection()->ExportCredentials();
		}

		craft()->systemSettings->saveSettings('rackspace', $currentSettings);
	}

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'RackSpace Cloud';
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
			'username'   => array(AttributeType::String, 'required' => true),
			'apiKey'     => array(AttributeType::String, 'required' => true),
			'region'     => array(AttributeType::String, 'required' => true),
			'container'	 => array(AttributeType::String, 'required' => true),
			'urlPrefix'  => array(AttributeType::String, 'required' => true),
			'subfolder'  => array(AttributeType::String, 'default' => ''),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/assetsourcetypes/Rackspace/settings', array(
			'settings' => $this->getSettings()
		));
	}


	/**
	 * Get bucket list with credentials.
	 *
	 * @param $username
	 * @param $apiKey
	 * @param $region
	 * @return array
	 * @throws Exception
	 */
	public static function getContainerList($username, $apiKey, $region)
	{
		$rackspace = AssetsHelper::getRackspaceConnectionUsingApiKey($username, $apiKey);

		try
		{
			$containers = $rackspace->ObjectStore(static::RackspaceServiceName, $region)->CDN()->ContainerList();
		}
		catch (\Exception $exception)
		{
			throw new Exception($exception->getMessage());
		}

		$containerList = array();

		while($container = $containers->Next())
		{
			/** @var \OpenCloud\ObjectStore\Container $container */
			$containerList[] = (object) array('container' => $container->name, 'urlPrefix' => $container->cdn_uri);
		}

		return $containerList;
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		$offset = 0;
		$total = 0;

		
		$prefix = $this->_getPathPrefix();

		$files = $this->_getContainer()->ObjectList(array('prefix' => $prefix));

		$fileList = array();
		while ($file = $files->Next())
		{
			/** @var \OpenCloud\ObjectStore\DataObject $file */
			$fileList[] = $file;

		}

		$fileList = array_filter($fileList, function ($value) {
			$path = $value->name;

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

		$containerFolders = array();

		foreach ($fileList as $file)
		{
			// Strip the prefix, so we don't index the parent folders
			$file->name = substr($file->name, strlen($prefix));

			if (!preg_match(AssetsHelper::IndexSkipItemsPattern, $file->name))
			{
				// So in RackSpace a folder may or may not exist. For path a/path/to/file.jpg, any of those folders may
				// or may not exist. So we have to add all the segments to $containerFolders to make sure we index them

				// Matches all paths with folders, except if there if no folder at all.
				if (preg_match('/(.*\/).+$/', $file->name, $matches))
				{
					$folders = explode('/', rtrim($matches[1], '/'));
					$basePath = '';

					foreach ($folders as $folder)
					{
						$basePath .= $folder;

						// This is exactly the case referred to above
						if ( ! isset($containerFolders[$basePath]))
						{
							$containerFolders[$basePath] = true;
						}

						$basePath .= '/';
					}
				}

				if ($file->content_type == 'application/directory')
				{
					$containerFolders[$file->name] = true;
				}
				else
				{
					$indexEntry = array(
						'sourceId' => $this->model->id,
						'sessionId' => $sessionId,
						'offset' => $offset++,
						'uri' => $file->name,
						'size' => $file->bytes
					);

					craft()->assetIndexing->storeIndexEntry($indexEntry);
					$total++;
				}
			}
		}

		$indexedFolderIds = array();
		$indexedFolderIds[craft()->assetIndexing->ensureTopFolder($this->model)] = true;

		// Ensure folders are in the DB
		foreach ($containerFolders as $fullPath => $nothing)
		{
			$folderId = $this->_ensureFolderByFulPath($fullPath.'/');
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
		


		if ($fileModel)
		{
			craft()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;

			$fileInfo = $this->_getObjectInfo($this->_getPathPrefix().$uriPath);
			$timeModified = new DateTime($fileInfo->last_modified, new \DateTimeZone('UTC'));

			$targetPath = craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $timeModified || !IOHelper::fileExists($targetPath))
			{
				$this->_getContainer()->DataObject($this->_getPathPrefix().$uriPath)->SaveToFilename($targetPath);
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

		$uriPath = $this->_getPathPrefix().$folder->fullPath.$fileName;

		

		$fileInfo = $this->_getObjectInfo($uriPath);

		if ($fileInfo)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->_getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		clearstatcache();
		

		// Upload file
		try
		{
			$this->_getContainer()->DataObject()->Create(array('name' => $uriPath, 'content_type' => IOHelper::getMimeType($filePath)), $filePath);
		}
		catch (\Exception $exception)
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
		return craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);
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
		$path = $this->_getPathPrefix().$folder->fullPath.$transformLocation.'/'.$fileModel->filename;
		
		$fileInfo = $this->_getObjectInfo($path);

		if (empty($fileInfo))
		{
			return false;
		}

		return new DateTime($fileInfo->last_modified, new \DateTimeZone('UTC'));
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
		
		$targetFile = $this->_getPathPrefix().$fileModel->getFolder()->fullPath.'_'.ltrim($handle, '_').'/'.$fileModel->filename;

		// Upload file
		try
		{
			$this->_getContainer()->DataObject()->Create(array('name' => $targetFile, 'content_type' => IOHelper::getMimeType($sourceImage)), $sourceImage);
			return true;
		}
		catch (\Exception $exception)
		{
			return false;
		}
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
		
		$files = $this->_getContainer()->ObjectList(array('prefix' => $this->_getPathPrefix().$folder->fullPath));

		$fileList = array();

		while ($file = $files->Next())
		{
			/** @var \OpenCloud\ObjectStore\DataObject $file */
			$fileList[$file->name] = true;
		}

		$fileNameParts = explode(".", $fileName);
		$extension = array_pop($fileNameParts);

		$fileNameStart = join(".", $fileNameParts) . '_';
		$index = 1;

		while ( isset($fileList[$this->_getPathPrefix().$folder->fullPath . $fileNameStart . $index . '.' . $extension]))
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

		
		$this->_getContainer()->DataObject($this->_getRackspacePath($file))->SaveToFilename($location);

		return $location;
	}

	/**
	 * Get a file's S3 path.
	 *
	 * @param AssetFileModel $file
	 * @return string
	 */
	private function _getRackspacePath(AssetFileModel $file)
	{
		$folder = $file->getFolder();
		return $this->_getPathPrefix().$folder->fullPath.$file->filename;
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
		try
		{
			$this->_getContainer()->DataObject($this->_getPathPrefix().$folder->fullPath.$filename)->Delete();
		}
		catch (\Exception $exception)
		{
			// Okay, so mission accomplished.
			;
		}
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
		

		foreach ($transforms as $location)
		{
			try
			{
				$this->_getContainer()->DataObject($this->_getPathPrefix().$folder->fullPath.$location.'/'.$file->filename)->Delete();
			}
			catch (\Exception $exception)
			{
				// Okay, so mission accomplished.
				;
			}
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

		$newServerPath = $this->_getPathPrefix().$targetFolder->fullPath.$fileName;

		$conflictingRecord = craft()->assets->findFile(array(
			'folderId' => $targetFolder->id,
			'filename' => $fileName
		));

		
		$fileInfo = $this->_getObjectInfo($newServerPath);

		$conflict = $fileInfo || (!craft()->assets->isMergeInProgress() && is_object($conflictingRecord));

		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->_getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		$sourceFolder = $file->getFolder();

		// Get the originating source object.
		$originatingSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$originatingSettings = $originatingSourceType->getSettings();
		$sourceContainer = $this->_getContainer($originatingSettings);
		$sourceObject = $sourceContainer->DataObject($originatingSettings->subfolder.$sourceFolder->fullPath.$file);

		$targetObject = $this->_getContainer()->DataObject();
		$targetObject->Create(array('name' => $newServerPath));
		$sourceObject->Copy($targetObject);

		$sourceObject->Delete();

		if ($file->kind == 'image')
		{
			$this->_deleteGeneratedThumbnails($file);

			// Move transforms
			$transforms = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);

			$baseFromPath = $originatingSettings->subfolder.$sourceFolder->fullPath;
			$baseToPath = $this->_getPathPrefix().$targetFolder->fullPath;

			foreach ($transforms as $location)
			{

				$sourceObject = $sourceContainer->DataObject(($baseFromPath.$location.'/'.$file->filename));
				$targetObject = $this->_getContainer()->DataObject();
				$targetObject->Create(array('name' => $baseToPath.$location.'/'.$fileName));

				try {
					$copyResult = $sourceObject->Copy($targetObject);
					$sourceObject->Delete();
				}
				catch (\Exception $exception)
				{
					;
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

		
		return (bool) $this->_getObjectInfo($this->_getPathPrefix().$parentFolder->fullPath.$folderName);

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
		
		return (bool) $this->_getContainer()->DataObject()->Create(array('name' => $this->_getPathPrefix().$parentFolder->fullPath.$folderName, 'content_type' => 'application/directory'));
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
		$newFullPath = $this->_getPathPrefix().$this->_getParentFullPath($folder->fullPath).$newName.'/';

		
		$objectList = $this->_getContainer()->ObjectList(array('prefix' => $this->_getPathPrefix().$folder->fullPath));
		$filesToMove = array();
		while($object = $objectList->Next())
		{
			$filesToMove[$object->name] = $object;
		}

		krsort($filesToMove);

		foreach ($filesToMove as $file)
		{
			$filePath = substr($file->name, strlen($this->_getPathPrefix().$folder->fullPath));

			$sourceObject = $this->_getContainer()->DataObject($file->name);
			$targetObject = $this->_getContainer()->DataObject();
			$targetObject->Create(array('name' => $newFullPath.$filePath));

			$sourceObject->Copy($targetObject);
			$sourceObject->Delete();
		}

		try{
			// This may or may not exist.
			$this->_getContainer()->DataObject($this->_getPathPrefix().rtrim($folder->fullPath, '/'))->Delete();
		}
		catch (\Exception $exception)
		{
			;
		}

		return TRUE;
	}

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @return boolean
	 */
	protected function _deleteSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		
		$objectsToDelete = $this->_getContainer()->ObjectList(array('prefix' => $this->_getPathPrefix().$parentFolder->fullPath.$folderName));

		while($file = $objectsToDelete->Next())
		{
			$this->_getContainer()->DataObject($file->name)->Delete();
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
			if ($settings->username == $theseSettings->username && $settings->apiKey == $theseSettings->apiKey)
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
		
		$basePath = $this->_getPathPrefix().$file->getFolder()->fullPath;
		$sourceObject = $this->_getContainer()->DataObject($basePath.$source.'/'.$file->filename);
		$targetObject = $this->_getContainer()->DataObject();
		$targetObject->Create(array('name' => $basePath.$target.'/'.$file->filename));
		$sourceObject->Copy($targetObject);
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
		return (bool) $this->_getObjectInfo($this->_getPathPrefix().$file->getFolder()->fullPath.$location.'/'.$file->filename);
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
	 * Prepare the RackSpace object for requests.
	 *
	 * @param $settings BaseModel
	 * @return \OpenCloud\ObjectStore\Container
	 */
	private function _getContainer($settings = null)
	{
		static $defaultSettings = null;

		// No settings passed, load default and cache them in static variable.
		if (is_null($settings))
		{
			if (is_null($defaultSettings))
			{
				$defaultSettings = $this->getSettings();
			}
			$settings = $defaultSettings;
		}

		$key = $settings->username.$settings->apiKey.$settings->region.$settings->container;

		if (empty(static::$_rackspaceContainers[$key]))
		{
			if (empty(static::$_storedCredentials))
			{
				static::$_storedCredentials = craft()->systemSettings->getSettings('rackspace');
				static::$_credentialTimestamp = craft()->systemSettings->getCategoryTimeUpdated('rackspace');
			}

			if (isset(static::$_storedCredentials[$key]))
			{
				// Craft converts these to array on saving, while Rackspace expect objects.
				foreach (static::$_storedCredentials[$key]['catalog'] as &$catalog)
				{
					$catalog = (object) $catalog;
					foreach ($catalog->endpoints as &$endpoint)
					{
						$endpoint = (object) $endpoint;
					}
				}
				$connection = AssetsHelper::getRackspaceConnectionUsingStoredCredentials($settings->username, $settings->apiKey, static::$_storedCredentials[$key]);
			}
			else
			{
				$connection = AssetsHelper::getRackspaceConnectionUsingApiKey($settings->username, $settings->apiKey);
			}

			static::$_rackspaceContainers[$key] = $connection->ObjectStore(self::RackspaceServiceName, $settings->region)->Container(rawurlencode($settings->container));
		}

		return static::$_rackspaceContainers[$key];
	}

	/**
	 * Get object information by path
	 * @param $path
	 * @return bool|\OpenCloud\ObjectStore\DataObject
	 */
	private function _getObjectInfo($path)
	{
		try
		{
			$info = $this->_getContainer()->DataObject($path);
		}
		catch (\Exception $exception)
		{
			return false;
		}
		return $info;
	}

}
