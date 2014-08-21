<?php
namespace Craft;

craft()->requireEdition(Craft::Pro);

/**
 * The Rackspace asset source type class. Handles the implementation of Rackspace as an asset source type in Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 * @deprecated This class will most likely be removed in Craft 3.0.
 */
class RackspaceAssetSourceType extends BaseAssetSourceType
{
	// Constants
	// =========================================================================

	const RackspaceAuthHost = 'https://identity.api.rackspacecloud.com/v2.0/tokens';
	const RackspaceStorageOperation = 'storage';
	const RackspaceCDNOperation = 'cdn';

	// Properties
	// =========================================================================

	/**
	 * Stores access information.
	 *
	 * @var array
	 */
	private static $_accessStore = array();

	// Public Methods
	// =========================================================================

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'Rackspace Cloud Files';
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
	 * @throws Exception
	 * @return array
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
	 * Get region list.
	 *
	 * @return array
	 */
	public function getRegionList()
	{
		$this->_refreshConnectionInformation();
		$regions = array();

		foreach (static::$_accessStore as $key => $information)
		{
			$parts = explode('#', $key);
			$regions[] = end($parts);
		}

		return $regions;
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 *
	 * @throws Exception
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		$offset = 0;
		$total = 0;

		$prefix = $this->_getPathPrefix();

		try
		{
			$fileList = $this->_getFileList($prefix);
		}
		catch (Exception $exception)
		{
			return array('error' => $exception->getMessage());
		}

		$fileList = array_filter($fileList, function($value)
		{
			$path = $value->name;

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

		$containerFolders = array();

		foreach ($fileList as $file)
		{
			// Strip the prefix, so we don't index the parent folders
			$file->name = mb_substr($file->name, mb_strlen($prefix));

			if (!preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $file->name))
			{
				// So in Rackspace a folder may or may not exist. For path a/path/to/file.jpg, any of those folders may
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
					// Rackspace may or may not have these at the end.
					$file->name = rtrim($file->name, '/');
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
			$folderId = $this->ensureFolderByFullPath($fullPath.'/');
			$indexedFolderIds[$folderId] = true;
		}

		$missingFolders = $this->getMissingFolders($indexedFolderIds);

		return array('sourceId' => $this->model->id, 'total' => $total, 'missingFolders' => $missingFolders);
	}

	/**
	 * Process an indexing session.
	 *
	 * @param $sessionId
	 * @param $offset
	 *
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
		$fileModel = $this->indexFile($uriPath);

		if ($fileModel)
		{
			craft()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;

			$fileInfo = $this->_getObjectInfo($this->_getPathPrefix().$uriPath);

			$timeModified = new DateTime($fileInfo->lastModified, new \DateTimeZone('UTC'));

			$targetPath = craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.IOHelper::getExtension($fileModel->filename);

			if ($fileModel->kind == 'image' && $fileModel->dateModified != $timeModified || !IOHelper::fileExists($targetPath))
			{
				$this->_downloadFile($this->_getPathPrefix().$uriPath, $targetPath);

				clearstatcache();
				list ($fileModel->width, $fileModel->height) = getimagesize($targetPath);

				// Store the local source or delete - maxCacheCloudImageSize is king.
				craft()->assetTransforms->storeLocalSource($targetPath, $targetPath);
				craft()->assetTransforms->deleteSourceIfNecessary($targetPath);
			}

			$fileModel->dateModified = $timeModified;

			craft()->assets->storeFile($fileModel);

			return $fileModel->id;
		}

		return false;
	}

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 *
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
	 * @param string         $transformLocation
	 *
	 * @return mixed
	 */
	public function getTimeTransformModified(AssetFileModel $fileModel, $transformLocation)
	{
		$folder = $fileModel->getFolder();
		$path = $this->_getPathPrefix().$folder->path.$transformLocation.'/'.$fileModel->filename;

		$fileInfo = $this->_getObjectInfo($path);

		if (empty($fileInfo))
		{
			return false;
		}

		return new DateTime($fileInfo->lastModified, new \DateTimeZone('UTC'));
	}

	/**
	 * Put an image transform for the File and handle using the provided path to the source image.
	 *
	 * @param AssetFileModel $fileModel
	 * @param                $handle
	 * @param                $sourceImage
	 *
	 * @return mixed
	 */
	public function putImageTransform(AssetFileModel $fileModel, $handle, $sourceImage)
	{
		$targetFile = $this->_getPathPrefix().$fileModel->getFolder()->path.'_'.ltrim($handle, '_').'/'.$fileModel->filename;

		// Upload file
		try
		{
			$this->_uploadFile($targetFile, $sourceImage);
			return true;
		}
		catch (\Exception $exception)
		{
			return false;
		}
	}

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param AssetFileModel $file
	 *
	 * @return mixed
	 */

	public function getLocalCopy(AssetFileModel $file)
	{
		$location = AssetsHelper::getTempFilePath($file->getExtension());

		$this->_downloadFile($this->_getRackspacePath($file), $location);

		return $location;
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
	 * Return the source's base URL.
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->getSettings()->urlPrefix.$this->_getPathPrefix();
	}

	/**
	 * Copy a transform for a file from source location to target location.
	 *
	 * @param AssetFileModel $file
	 * @param                $source
	 * @param                $target
	 *
	 * @return mixed
	 */
	public function copyTransform(AssetFileModel $file, $source, $target)
	{
		$container = $this->getSettings()->container;
		$basePath = $this->_getPathPrefix().$file->getFolder()->path;

		$sourceUri = $this->_prepareRequestURI($container, $basePath.$source.'/'.$file->filename);
		$targetUri = $this->_prepareRequestURI($container, $basePath.$target.'/'.$file->filename);

		$this->_copyFile($sourceUri, $targetUri);
	}

	/**
	 * Return true if a transform exists at the location for a file.
	 *
	 * @param AssetFileModel $file
	 * @param                $location
	 *
	 * @return mixed
	 */
	public function transformExists(AssetFileModel $file, $location)
	{
		return (bool) $this->_getObjectInfo($this->_getPathPrefix().$file->getFolder()->path.$location.'/'.$file->filename);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param                  $fileName
	 *
	 * @return mixed
	 */
	protected function getNameReplacement(AssetFolderModel $folder, $fileName)
	{
		$prefix = $this->_getPathPrefix().$folder->path;

		$files = $this->_getFileList($prefix);

		$fileList = array();
		foreach ($files as $file)
		{
			$fileList[$file->name] = true;
		}

		// Double-check
		if (!isset($fileList[$fileName]))
		{
			return $fileName;
		}

		$fileNameParts = explode(".", $fileName);
		$extension = array_pop($fileNameParts);

		$fileNameStart = join(".", $fileNameParts).'_';
		$index = 1;

		while ( isset($fileList[$this->_getPathPrefix().$folder->path.$fileNameStart.$index.'.'.$extension]))
		{
			$index++;
		}

		return $fileNameStart.$index.'.'.$extension;
	}

	/**
	 * Defines the settings.
	 *
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
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param                  $filePath
	 * @param                  $fileName
	 *
	 * @throws Exception
	 * @return AssetFileModel
	 */
	protected function insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName)
	{
		$fileName = IOHelper::cleanFilename($fileName);
		$extension = IOHelper::getExtension($fileName);

		if (! IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Craft::t('This file type is not allowed'));
		}

		$uriPath = $this->_getPathPrefix().$folder->path.$fileName;

		$fileInfo = $this->_getObjectInfo($uriPath);

		if ($fileInfo)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		clearstatcache();

		// Upload file
		try
		{
			$this->_uploadFile($uriPath, $filePath);
		}
		catch (\Exception $exception)
		{
			throw new Exception(Craft::t('Could not copy file to target destination'));
		}

		$response = new AssetOperationResponseModel();
		return $response->setSuccess()->setDataItem('filePath', $uriPath);
	}

	/**
	 * Delete just the source file for an Assets File.
	 *
	 * @param AssetFolderModel $folder
	 * @param                  $filename
	 *
	 * @return null
	 */
	protected function deleteSourceFile(AssetFolderModel $folder, $filename)
	{
		$uriPath = $this->_prepareRequestURI($this->getSettings()->container, $this->_getPathPrefix().$folder->path.$filename);

		$this->_deleteObject($uriPath);

	}

	/**
	 * Delete all the generated image transforms for this file.
	 *
	 * @param AssetFileModel $file
	 *
	 * @return null
	 */
	protected function deleteGeneratedImageTransforms(AssetFileModel $file)
	{
		$folder = craft()->assets->getFolderById($file->folderId);
		$transforms = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);

		foreach ($transforms as $location)
		{
			$this->_deleteObject($this->_prepareRequestURI($this->getSettings()->container, $this->_getPathPrefix().$folder->path.$location.'/'.$file->filename));
		}
	}

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel   $file
	 * @param AssetFolderModel $targetFolder
	 * @param string           $fileName
	 * @param bool             $overwrite    If true, will overwrite target destination
	 *
	 * @return mixed
	 */
	protected function moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $overwrite = false)
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


		$fileInfo = $this->_getObjectInfo($newServerPath);

		$conflict = !$overwrite && ($fileInfo || (!craft()->assets->isMergeInProgress() && is_object($conflictingRecord)));

		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		$sourceFolder = $file->getFolder();

		// Get the originating source object.
		$originatingSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		$originatingSettings = $originatingSourceType->getSettings();

		$sourceUri = $this->_prepareRequestURI($originatingSettings->container, $originatingSettings->subfolder.$sourceFolder->path.$file->filename);
		$targetUri = $this->_prepareRequestURI($this->getSettings()->container, $newServerPath);

		$this->_copyFile($sourceUri, $targetUri);
		$this->_deleteObject($sourceUri);

		if ($file->kind == 'image')
		{
			$this->deleteGeneratedThumbnails($file);

			// Move transforms
			$transforms = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);

			$baseFromPath = $originatingSettings->subfolder.$sourceFolder->path;
			$baseToPath = $this->_getPathPrefix().$targetFolder->path;

			foreach ($transforms as $location)
			{
				$sourceUri = $this->_prepareRequestURI($originatingSettings->container, $baseFromPath.$location.'/'.$file->filename);
				$targetUri = $this->_prepareRequestURI($this->getSettings()->container, $baseToPath.$location.'/'.$fileName);
				$this->_copyFile($sourceUri, $targetUri);
				$this->_deleteObject($sourceUri);
			}
		}

		$response = new AssetOperationResponseModel();
		return $response->setSuccess()
				->setDataItem('newId', $file->id)
				->setDataItem('newFileName', $fileName);
	}

	/**
	 * Return true if a folder exists on Rackspace.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param                  $folderName
	 *
	 * @return bool
	 */
	protected function sourceFolderExists(AssetFolderModel $parentFolder, $folderName)
	{
		return (bool) $this->_getObjectInfo($this->_getPathPrefix().$parentFolder->path.$folderName);

	}

	/**
	 * Create a folder on Rackspace, return true on success.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param                  $folderName
	 *
	 * @return bool
	 */
	protected function createSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$headers = array(
			'Content-type: application/directory',
			'Content-length: 0'
		);

		$targetUri = $this->_prepareRequestURI($this->getSettings()->container, $this->_getPathPrefix().$parentFolder->path.$folderName);

		$this->_doAuthenticatedRequest(static::RackspaceStorageOperation,  $targetUri, 'PUT', $headers);
		return true;
	}

	/**
	 * Rename a source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param                  $newName
	 *
	 * @return bool
	 */
	protected function renameSourceFolder(AssetFolderModel $folder, $newName)
	{
		$newFullPath = $this->_getPathPrefix().IOHelper::getParentFolderPath($folder->path).$newName.'/';

		$objectList = $this->_getFileList($this->_getPathPrefix().$folder->path);
		$filesToMove = array();

		foreach ($objectList as $object)
		{
			$filesToMove[$object->name] = $object;
		}

		krsort($filesToMove);

		foreach ($filesToMove as $file)
		{
			$filePath = mb_substr($file->name, mb_strlen($this->_getPathPrefix().$folder->path));

			$sourceUri = $this->_prepareRequestURI($this->getSettings()->container, $file->name);
			$targetUri = $this->_prepareRequestURI($this->getSettings()->container, $newFullPath.$filePath);
			$this->_copyFile($sourceUri, $targetUri);
			$this->_deleteObject($sourceUri);
		}

		// This may or may not exist.
		$this->_deleteObject($this->_prepareRequestURI($this->getSettings()->container, $this->_getPathPrefix().rtrim($folder->path, '/')));

		return true;
	}

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param string           $folderName
	 *
	 * @return bool
	 */
	protected function deleteSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$container = $this->getSettings()->container;
		$objectsToDelete = $this->_getFileList($this->_getPathPrefix().$parentFolder->path.$folderName);

		foreach ($objectsToDelete as $file)
		{
			$this->_deleteObject($this->_prepareRequestURI($container, $file->name));
		}

		$this->_deleteObject($this->_prepareRequestURI($container, $this->_getPathPrefix().$parentFolder->path.$folderName));

		return true;
	}

	/**
	 * Determines if a file can be moved internally from original source.
	 *
	 * @param BaseAssetSourceType $originalSource
	 *
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
	 * Purge a file from Akamai CDN.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filename
	 *
	 * @return null
	 */
	protected function purgeCachedSourceFile(AssetFolderModel $folder, $filename)
	{
		$uriPath = $this->_prepareRequestURI($this->getSettings()->container, $this->_getPathPrefix().$folder->path.$filename);
		$this->_purgeObject($uriPath);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Create the authorization request URL
	 *
	 * @return string
	 */
	private static function _makeAuthorizationRequestUrl()
	{
		return static::RackspaceAuthHost;
	}

	/**
	 * Load Rackspace access data from DB.
	 *
	 * @return null
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
	 * Update or insert access data for a connection key.
	 *
	 * @param $connectionKey
	 * @param $data
	 *
	 * @return null
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
	 *
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
	 *
	 * @return string
	 */
	private static function _extractRequestResponse($response)
	{
		return rtrim(mb_substr($response, mb_strpos($response, "\r\n\r\n") + 4));
	}

	/**
	 * Log an unexpected response.
	 *
	 * @param $response
	 *
	 * @return null
	 */
	private static function _logUnexpectedResponse($response)
	{
		Craft::log('RACKSPACE: Received unexpected response: '.$response, LogLevel::Error);
	}

	/**
	 * Make a request and return the response.
	 *
	 * @param $url
	 * @param $method
	 * @param $headers
	 * @param $curlOptions
	 * @param $payload
	 *
	 * @return string
	 */
	private static function _doRequest($url, $method = 'GET', $headers = array(), $curlOptions = array(), $payload = '')
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

		if ($method == "POST")
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}


		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	/**
	 * Upload a file to Rackspace.
	 *
	 * @param $targetUri
	 * @param $sourceFile
	 *
	 * @return bool
	 */
	private function _uploadFile($targetUri, $sourceFile)
	{
		$fileSize = IOHelper::getFileSize($sourceFile);
		$fp = fopen($sourceFile, "r");

		$headers = array(
			'Content-type: '.IOHelper::getMimeType($sourceFile),
			'Content-length: '.$fileSize
		);

		$curlOptions = array(
			CURLOPT_UPLOAD => true,
			CURLOPT_INFILE => $fp,
			CURLOPT_INFILESIZE => $fileSize
		);

		$targetUri = $this->_prepareRequestURI($this->getSettings()->container, $targetUri);
		$this->_doAuthenticatedRequest(static::RackspaceStorageOperation, $targetUri, 'PUT', $headers, $curlOptions);
		fclose($fp);

		return true;
	}

	/**
	 * Return a prefix for S3 path for settings.
	 *
	 * @param object|null $settings The settings to use. If null, will use the current settings.
	 *
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

		return '';
	}

	/**
	 * Refresh a connection information and return authorization token.
	 *
	 * @throws Exception
	 * @return null
	 */
	private function _refreshConnectionInformation()
	{
		$settings = $this->getSettings();
		$username = $settings->username;
		$apiKey = $settings->apiKey;

		$headers = array(
			'Content-Type: application/json',
			'Accept: application/json',

		);

		$payload = json_encode(array(
			'auth' => array(
				'RAX-KSKEY:apiKeyCredentials' => array(
					'username' => $username,
					'apiKey' => $apiKey
				)
			)
		));

		$targetUrl = static::_makeAuthorizationRequestUrl();
		$response = static::_doRequest($targetUrl, 'POST', $headers, array(), $payload);
		$body = json_decode(substr($response, strpos($response, '{')));

		if (empty($body->access))
		{
			throw new Exception(Craft::t("Wrong credentials supplied for Rackspace access!"));
		}

		$token = $body->access->token->id;
		$services = $body->access->serviceCatalog;

		if (!$token || !$services)
		{
			throw new Exception(Craft::t("Wrong credentials supplied for Rackspace access!"));
		}

		$regions = array();

		// Fetch region information
		foreach ($services as $service)
		{
			if ($service->name == 'cloudFilesCDN' || $service->name == 'cloudFiles')
			{
				foreach ($service->endpoints as $endpoint)
				{
					if (empty($regions[$endpoint->region]))
					{
						$regions[$endpoint->region] = array();
					}

					if ($service->name == 'cloudFilesCDN')
					{
						$regions[$endpoint->region]['cdnUrl'] = $endpoint->publicURL;
					}
					else
					{
						$regions[$endpoint->region]['storageUrl'] = $endpoint->publicURL;
					}
				}
			}
		}

		// Each region gets separate connection information
		foreach ($regions as $region => $data)
		{
			$connection_key = $this->_getConnectionKey($username, $apiKey, $region);
			$data = array('token' => $token, 'storageUrl' => $data['storageUrl'], 'cdnUrl' => $data['cdnUrl']);

			// Store this in the access store
			static::$_accessStore[$connection_key] = $data;
			$this->_updateAccessData($connection_key, $data);

		}
	}

	/**
	 * Get object information by path.
	 *
	 * @param $path
	 *
	 * @return bool|object
	 */
	private function _getObjectInfo($path)
	{
		$target = $this->_prepareRequestURI($this->getSettings()->container, $path);
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
	 * @param string $operationType Operation type so we know which server to target.
	 * @param string $target        URI target on the Rackspace server.
	 * @param string $method        GET/POST/PUT/DELETE
	 * @param array  $headers       Array of headers. Authorization token will be appended to this before request.
	 * @param array  $curlOptions   Additional curl options to set.
	 *
	 * @throws Exception
	 * @return string The full response including headers.
	 */
	private function _doAuthenticatedRequest($operationType, $target = '', $method = 'GET', $headers = array(), $curlOptions = array())
	{
		$settings = $this->getSettings();

		$username = $settings->username;
		$apiKey = $settings->apiKey;
		$region = $settings->region;

		if (empty($region) || $region == '-')
		{
			throw new Exception(Craft::t("Please update your Rackspace source settings, including the container's region information for this source to work."));
		}

		$connectionKey = $this->_getConnectionKey($username, $apiKey, $region);

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

		$headers[] = 'X-Auth-Token: '.$connectionInformation['token'];

		switch ($operationType)
		{
			case static::RackspaceStorageOperation:
			{
				$url = rtrim($connectionInformation['storageUrl'], '/').'/'.$target;
				break;
			}

			case static::RackspaceCDNOperation:
			{
				$url = rtrim($connectionInformation['cdnUrl'], '/').'/'.$target;
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
							if (mb_strpos($header, 'X-Auth-Token') === false)
							{
								$newHeaders[] = $header;
							}
						}

						return $this->_doAuthenticatedRequest($operationType, $target, $method, $newHeaders);
					}
					throw new Exception(Craft::t('Token has expired and the attempt to renew it failed. Please check the source settings.'));
					break;
				}

			}
		}

		return $response;
	}

	/**
	 * Download a file to the target location. The file will be downloaded using the public URL, instead of cURL.
	 *
	 * @param $path
	 * @param $targetFile
	 *
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

	/**
	 * Get file list from Rackspace.
	 *
	 * @param $prefix
	 *
	 * @throws Exception
	 * @return mixed
	 */
	private function _getFileList($prefix = '')
	{
		$targetUri = $this->_prepareRequestURI($this->getSettings()->container).'?prefix='.$prefix.'&format=json';
		$response = $this->_doAuthenticatedRequest(static::RackspaceStorageOperation, $targetUri);

		$extractedResponse = static::_extractRequestResponse($response);
		$fileList = json_decode($extractedResponse);

		if (!is_array($fileList))
		{
			static::_logUnexpectedResponse($response);
			throw new Exception(Craft::t('Remote server for “{source}” returned an unexpected response.', array('source' => $this->model->name)));
		}

		return $fileList;
	}

	/**
	 * Delete a file on Rackspace.
	 *
	 * @param $uriPath
	 *
	 * @return null
	 */
	private function _deleteObject($uriPath)
	{
		$this->_doAuthenticatedRequest(static::RackspaceStorageOperation, $uriPath, 'DELETE');
	}

	/**
	 * Purge a file from Akamai CDN
	 *
	 * @param $uriPath
	 *
	 * @return null
	 */
	private function _purgeObject($uriPath)
	{
		$this->_doAuthenticatedRequest(static::RackspaceCDNOperation, $uriPath, 'DELETE');
	}

	/**
	 * Copy a file on Rackspace.
	 *
	 * @param $sourceUri
	 * @param $targetUri
	 *
	 * @return null
	 */
	private function _copyFile($sourceUri, $targetUri)
	{
		$targetUri = '/'.ltrim($targetUri, '/');
		$this->_doAuthenticatedRequest(static::RackspaceStorageOperation, $sourceUri, 'COPY', array('Destination: '.$targetUri));
	}

	/**
	 * Prepare a request URI by container and target path.
	 *
	 * @param $container
	 * @param $uri
	 *
	 * @return string
	 */
	private function _prepareRequestURI($container, $uri = '')
	{
		return rawurlencode($container).(!empty($uri) ? '/'.rawurlencode($uri) : '');
	}

	/**
	 * Get a connection key by parameters.
	 *
	 * @param $username
	 * @param $apiKey
	 * @param $region
	 *
	 * @return string
	 */
	private function _getConnectionKey($username, $apiKey, $region)
	{
		return implode('#', array($username, $apiKey, $region));
	}

	/**
	 * Get a file's S3 path.
	 *
	 * @param AssetFileModel $file
	 *
	 * @return string
	 */
	private function _getRackspacePath(AssetFileModel $file)
	{
		$folder = $file->getFolder();
		return $this->_getPathPrefix().$folder->path.$file->filename;
	}

}
