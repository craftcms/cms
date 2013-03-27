<?php
namespace Craft;

Craft::requirePackage(CraftPackage::Cloud);

/**
 * Rackspace source type class
 */
class RackspaceAssetSourceType extends BaseAssetSourceType
{

	const RackspaceServiceName = 'cloudFiles';
	const RackspaceUSAuthHost = 'https://identity.api.rackspacecloud.com/v1.0';
	const RackspaceUKAuthHost = 'https://lon.identity.api.rackspacecloud.com/v1.0';

	const RackspaceStorageOperation = 'storage';
	const RackspaceCDNOperation = 'cdn';

	/**
	 * Stores access information.
	 *
	 * @var array
	 */
	private static $_accessStore = array();

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
			'location'   => array(AttributeType::String, 'required' => true),
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
	 * Get container list.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getContainerList()
	{

		$response = $this->_doAuthenticatedRequest(static::RackspaceCDNOperation, '?format=json');

		$extractedResponse = static::_extractRequestResponse($response);
		$data = json_decode($extractedResponse);

		$returnData = array();
		if (is_array($data))
		{
			foreach ($data as $container)
			{
				$returnData[] = (object) array('container' => $container->name, 'urlPrefix' => rtrim($container->cdn_uri, '/').'/');
			}
		}
		else
		{
			static::_logUnexpectedResponse($response);
		}

		return $returnData;
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 * @throws Exception
	 */
	public function startIndex($sessionId)
	{
		$offset = 0;
		$total = 0;

		
		$prefix = $this->_getPathPrefix();

		$response = $this->_doAuthenticatedRequest(static::RackspaceStorageOperation, '/'.rawurlencode($this->getSettings()->container).'?prefix='.$prefix.'&format=json');
		$extractedResponse = static::_extractRequestResponse($response);
		$fileList = json_decode($extractedResponse);

		if (!is_array($fileList))
		{
			static::_logUnexpectedResponse($response);
			return array('sourceId' => $this->model->id, 'error' => Craft::t('Remote server for “{source}” returned an unexpected response.', array('source' => $this->model->name)));
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

			$timeModified = new DateTime($fileInfo->lastModified, new \DateTimeZone('UTC'));

			$targetPath = craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel->filename, PATHINFO_EXTENSION);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $timeModified || !IOHelper::fileExists($targetPath))
			{
				$this->_downloadFile($this->_getPathPrefix().$uriPath, $targetPath);

				clearstatcache();
				//list ($fileModel->width, $fileModel->height) = getimagesize($targetPath);
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

		$this->_downloadFile($this->_getRackspacePath($file), $location);

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

		// Target object.
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
	 * Refresh a connection information and return authorization token.
	 *
	 * @param $username
	 * @param $apiKey
	 * @throws Exception
	 */
	private function _refreshConnectionInformation()
	{
		$settings = $this->getSettings();
		$username = $settings->username;
		$apiKey = $settings->apiKey;
		$location = $settings->location;

		$headers = array(
			'X-Auth-User: '.$username,
			'X-Auth-Key: '.$apiKey
		);

		$targetUrl = static::_makeAuthorizationRequestUrl($location);
		$response = static::_doRequest($targetUrl, 'GET', $headers);

		// Extract the values
		$token = static::_extractHeader($response, 'X-Auth-Token');
		$storageUrl = static::_extractHeader($response, 'X-Storage-Url');
		$cdnUrl = static::_extractHeader($response, 'X-CDN-Management-Url');

		if (!($token && $storageUrl && $cdnUrl))
		{
			throw new Exception(Craft::t("Wrong credentials supplied for Rackspace access!"));
		}

		$connectionKey = $username.$apiKey;

		$data = array('token' => $token, 'storageUrl' => $storageUrl, 'cdnUrl' => $cdnUrl);

		// Store this in the access store
		static::$_accessStore[$connectionKey] = $data;

		// And update DB information.
		static::_updateAccessData($connectionKey, $data);
	}


	/**
	 * Create the authorization request URL by location
	 *
	 * @param string $location
	 * @return string
	 */
	private static function _makeAuthorizationRequestUrl($location = '')
	{
		if ($location == 'uk')
		{
			return static::RackspaceUKAuthHost;
		}

		return static::RackspaceUSAuthHost;
	}

	/**
	 * Make a request and return the response.
	 *
	 * @param $url
	 * @param $method
	 * @param $headers
	 * @return string
	 */
	private static function _doRequest($url, $method = 'GET', $headers = array(), $curlOptions = array())
	{
		$ch = curl_init($url);
		if ($method == 'HEAD')
		{
			curl_setopt($ch, CURLOPT_NOBODY, 1);
		}
		else
		{
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		}

		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		foreach ($curlOptions as $option => $value)
		{
			curl_setopt($ch, $option, $value);
		}

		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
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
			if (empty(static::$_storedContainers))
			{
				static::$_storedContainers = craft()->systemSettings->getSettings('rackspace');
			}

			if (isset(static::$_storedContainers[$key]))
			{
				static::$_rackspaceContainers[$key] = @unserialize(static::$_storedContainers[$key]);
			}

			// This is fresh information or some corrupt data - either way we'll fetch a new one and save it.
			if (!isset(static::$_rackspaceContainers[$key]) || !is_object(static::$_rackspaceContainers[$key]))
			{
				static::$_rackspaceContainers[$key] = AssetsHelper::getRackspaceConnection($settings->username, $settings->apiKey)->ObjectStore(self::RackspaceServiceName, $settings->region)->Container(rawurlencode($settings->container));
				$dataToSave = array();
				foreach (static::$_rackspaceContainers as $key => $container)
				{
					$dataToSave[$key] = serialize($container);
				}

				craft()->systemSettings->saveSettings('rackspace', $dataToSave);
			}
		}

		return static::$_rackspaceContainers[$key];
	}

	/**
	 * Get object information by path
	 * @param $path
	 * @return bool|object
	 */
	private function _getObjectInfo($path)
	{

		$target = '/'.rawurlencode($this->getSettings()->container).'/'.rawurlencode($path);
		$response = $this->_doAuthenticatedRequest(static::RackspaceStorageOperation, $target, 'HEAD');

		$lastModified = static::_extractHeader($response, 'Last-Modified');
		$size = static::_extractHeader($response, 'Content-Length');

		if (!$lastModified)
		{
			return false;
		}

		return (object) array('lastModified' => $lastModified, 'size' => $size);
	}

	/**
	 * Do an authenticated request against Rackspace severs.
	 *
	 * @param string $operationType operation type so we know which server to target
	 * @param string $target URI target on the Rackspace server
	 * @param string $method GET/POST/PUT/DELETE
	 * @param array $headers array of headers. Authorization token will be appended to this before request.
	 * @param array $curlOptions additional curl options to set.
	 * @return string full response including headers.
	 * @throws Exception
	 */
	private function _doAuthenticatedRequest($operationType, $target = '', $method = 'GET', $headers = array(), $curlOptions = array())
	{
		$settings = $this->getSettings();

		$username = $settings->username;
		$apiKey = $settings->apiKey;

		$connectionKey = $username.$apiKey;

		// If we don't have the access information, load it from DB
		if (empty(static::$_accessStore[$connectionKey]))
		{
			static::_loadAccessData();
		}

		// If we still don't have it, fetch it using username and api key.
		if (empty(static::$_accessStore[$connectionKey]))
		{
			$this->_refreshConnectionInformation();
		}

		// If we still don't have it, then we're all out of luck.
		if (empty(static::$_accessStore[$connectionKey]))
		{
			throw new Exception(Craft::t("Connection information not found!"));
		}

		$connectionInformation = static::$_accessStore[$connectionKey];

		$headers[] = 'X-Auth-Token: ' . $connectionInformation['token'];

		switch ($operationType)
		{
			case static::RackspaceStorageOperation:
			{
				$url = $connectionInformation['storageUrl'].$target;
				break;
			}

			case static::RackspaceCDNOperation:
			{
				$url = $connectionInformation['cdnUrl'].$target;
				break;
			}

			default:
			{
				throw new Exception(Craft::t("Unrecognized operation type!"));
			}
		}

		$response = static::_doRequest($url, $method, $headers, $curlOptions);

		preg_match('/HTTP\/1.1 (?P<httpStatus>[0-9]{3})/', $response, $matches);

		if (!empty($matches['httpStatus']))
		{
			// Error checking
			switch ($matches['httpStatus'])
			{
				// Invalid token - try to renew it once.
				case '401':
				{
					static $tokenFailure = 0;
					if (++$tokenFailure == 1)
					{
						$this->_refreshConnectionInformation();

						// Remove token header.
						$newHeaders = array();
						foreach ($headers as $header)
						{
							if (strpos($header, 'X-Auth-Token') === false)
							{
								$newHeaders[] = $header;
							}
						}

						return $this->_doAuthenticatedRequest($operationType, $target, $method, $newHeaders);
					}
					throw new Exception("Token has expired and the attempt to renew it failed. Please check the source settings.");
					break;
				}

			}
		}

		return $response;
	}

	/**
	 * Load Rackspace access data from DB.
	 */
	private static function _loadAccessData()
	{
		$rows = craft()->db->createCommand()->select('connectionKey, token, storageUrl, cdnUrl')->from('rackspaceaccess')->queryAll();
		foreach ($rows as $row)
		{
			static::$_accessStore[$row['connectionKey']] = array(
															'token' => $row['token'],
															'storageUrl' => $row['storageUrl'],
															'cdnUrl' => $row['cdnUrl']);
		}
	}

	/**
	 * Update or insert access data for a connetion key.
	 *
	 * @param $connectionKey
	 * @param $data
	 */
	private static function _updateAccessData($connectionKey, $data)
	{
		$recordExists = craft()->db->createCommand()
			->select('id')
			->where('connectionKey = :connectionKey', array(':connectionKey' => $connectionKey))
			->from('rackspaceaccess')
			->queryScalar();

		if ($recordExists)
		{
			craft()->db->createCommand()->update('rackspaceaccess', $data, 'id = :id', array(':id' => $recordExists));
		}
		else
		{
			$data['connectionKey'] = $connectionKey;
			craft()->db->createCommand()->insert('rackspaceaccess', $data);
		}
	}

	/**
	 * Extract a header from a response.
	 *
	 * @param $response
	 * @param $header
	 * @return mixed
	 */
	private static function _extractHeader($response, $header)
	{
		preg_match('/.*'.$header.': (?P<value>.+)\r/', $response, $matches);
		return isset($matches['value']) ? $matches['value'] : false;
	}



	/**
	 * Extract the response form a response that has headers.
	 *
	 * @param $response
	 * @return string
	 */
	private static function _extractRequestResponse($response)
	{
		return rtrim(substr($response, strpos($response, "\r\n\r\n") + 4));
	}

	/**
	 * Log an unexpected response.
	 *
	 * @param $response
	 */
	private static function _logUnexpectedResponse($response)
	{
		Craft::log("RACKSPACE: Received unexpected response: " . $response);
	}

	/**
	 * Download a file to the target location. The file will be downloaded using the public URL, instead of cURL.
	 *
	 * @param $path
	 * @param $targetFile
	 * @return bool
	 */
	private function _downloadFile($path, $targetFile)
	{
		$target = $this->getSettings()->urlPrefix.$path;

		$ch = curl_init($target);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		IOHelper::writeToFile($targetFile, $response);

		return true;
	}
}
