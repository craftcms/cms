<?php
namespace Craft;

/**
 * Local source type class
 */
class LocalAssetSourceType extends BaseAssetSourceType
{
	protected $_isSourceLocal = true;

	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Local Folder');
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
			'path' => array(AttributeType::String, 'required' => true),
			'url'  => array(AttributeType::String, 'required' => true, 'label' => 'URL'),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/assetsourcetypes/Local/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		// Add a trailing slash to the Path and URL settings
		$settings['path'] = !empty($settings['path']) ? rtrim($settings['path'], '/').'/' : '';
		$settings['url'] = !empty($settings['url']) ? rtrim($settings['url'], '/').'/' : '';

		return $settings;
	}

	/**
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		$indexedFolderIds = array();

		$indexedFolderIds[craft()->assetIndexing->ensureTopFolder($this->model)] = true;

		$localPath = $this->_getSourceFileSystemPath();

		if ($localPath == '/' || !IOHelper::folderExists($localPath) || $localPath === false)
		{
			return array('sourceId' => $this->model->id, 'error' => Craft::t('The path of your source “{source}” appears to be invalid.', array('source' => $this->model->name)));
		}

		$fileList = IOHelper::getFolderContents($localPath, true);

		if ($fileList && is_array($fileList) && count($fileList) > 0)
		{
			$fileList = array_filter($fileList, function($value) use ($localPath)
			{
				$path = mb_substr($value, mb_strlen($localPath));
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
		}

		$offset = 0;
		$total = 0;

		foreach ($fileList as $file)
		{
			if (!preg_match(AssetsHelper::IndexSkipItemsPattern, $file))
			{
				if (is_dir($file))
				{
					$fullPath = rtrim(str_replace($this->_getSourceFileSystemPath(), '', $file), '/') . '/';
					$folderId = $this->_ensureFolderByFullPath($fullPath);
					$indexedFolderIds[$folderId] = true;
				}
				else
				{
					$indexEntry = array(
						'sourceId' => $this->model->id,
						'sessionId' => $sessionId,
						'offset' => $offset++,
						'uri' => $file,
						'size' => is_dir($file) ? 0 : filesize($file)
					);

					craft()->assetIndexing->storeIndexEntry($indexEntry);
					$total++;
				}
			}
		}

		$missingFolders = $this->_getMissingFolders($indexedFolderIds);

		return array('sourceId' => $this->model->id, 'total' => $total, 'missingFolders' => $missingFolders);
	}

	/**
	 * Get the file system path for upload source.
	 *
	 * @param BaseAssetSourceType|LocalAssetSourceType $sourceType = null
	 * @return string
	 */
	protected function _getSourceFileSystemPath(LocalAssetSourceType $sourceType = null)
	{
		$path = is_null($sourceType) ? $this->getBasePath() : $sourceType->getBasePath();
		$path = IOHelper::getRealPath($path);
		return $path;
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

		// Make sure we have a trailing slash. Some people love to skip those.
		$uploadPath = $this->_getSourceFileSystemPath();

		$file = $indexEntryModel->uri;

		// This is the part of the path that actually matters
		$uriPath = mb_substr($file, mb_strlen($uploadPath));

		$fileModel = $this->_indexFile($uriPath);

		if ($fileModel)
		{
			craft()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;
			$fileModel->dateModified = IOHelper::getLastTimeModified($indexEntryModel->uri);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($indexEntryModel->uri);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

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
	 * @return AssetOperationResponseModel
	 * @throws Exception
	 */
	protected function _insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName)
	{
		// Check if the set file system path exists
		$basePath = $this->_getSourceFileSystemPath();

		if (empty($basePath))
		{
			$basePath = $this->getBasePath();

			if (!empty($basePath))
			{
				throw new Exception(Craft::t('The file system path “{folder}” set for this source does not exist.', array('folder' => $this->getBasePath())));
			}
		}

		$targetFolder = $this->_getSourceFileSystemPath() . $folder->path;

		// Make sure the folder exists.
		if (!IOHelper::folderExists($targetFolder))
		{
			throw new Exception(Craft::t('The folder “{folder}” does not exist.', array('folder' => $targetFolder)));
		}

		// Make sure the folder is writable
		if (!IOHelper::isWritable($targetFolder))
		{
			throw new Exception(Craft::t('The folder “{folder}” is not writable.', array('folder' => $targetFolder)));
		}

		$fileName = IOHelper::cleanFilename($fileName);
		$targetPath = $targetFolder . $fileName;
		$extension = IOHelper::getExtension($fileName);

		if (!IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Craft::t('This file type is not allowed'));
		}

		if (IOHelper::fileExists($targetPath))
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->_getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		if (! IOHelper::copyFile($filePath, $targetPath))
		{
			throw new Exception(Craft::t('Could not copy file to target destination'));
		}

		IOHelper::changePermissions($targetPath, IOHelper::getWritableFilePermissions());

		$response = new AssetOperationResponseModel();
		return $response->setSuccess()->setDataItem('filePath', $targetPath);
	}

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @return string
	 */
	protected function _getNameReplacement(AssetFolderModel $folder, $fileName)
	{
		$fileList = IOHelper::getFolderContents($this->_getSourceFileSystemPath() . $folder->path, false);
		$existingFiles = array();

		foreach ($fileList as $file)
		{
			$existingFiles[IOHelper::getFileName($file)] = true;
		}

		// Double-check
		if (!isset($existingFiles[$fileName]))
		{
			return $fileName;
		}

		$fileParts = explode(".", $fileName);
		$extension = array_pop($fileParts);
		$fileName = join(".", $fileParts);

		for ($i = 1; $i <= 50; $i++)
		{
			if (!isset($existingFiles[$fileName.'_'.$i.'.'.$extension]))
			{
				return $fileName.'_'.$i.'.'.$extension;
			}
		}

		return false;
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
		$path = $this->_getImageServerPath($fileModel, $transformLocation);

		if (!IOHelper::fileExists($path))
		{
			return false;
		}

		return IOHelper::getLastTimeModified($path);
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
		return IOHelper::copyFile($sourceImage, $this->_getImageServerPath($fileModel, $handle));
	}

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $handle
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $fileModel, $handle = '')
	{
		return $this->_getImageServerPath($fileModel, $handle);
	}

	/**
	 * Get the local path for an image, optionally with a size handle.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $transformLocation
	 * @return string
	 */
	private function _getImageServerPath(AssetFileModel $fileModel, $transformLocation = '')
	{
		if (!empty($transformLocation))
		{
			$transformLocation = '_'.ltrim($transformLocation, '_');
		}

		$targetFolder = $this->_getSourceFileSystemPath().$fileModel->getFolder()->path;
		$targetFolder .= !empty($transformLocation) ? $transformLocation.'/': '';

		return $targetFolder.$fileModel->filename;
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
		IOHelper::copyFile($this->_getFileSystemPath($file), $location);
		clearstatcache();

		return $location;
	}

	/**
	 * Get a file's system path.
	 *
	 * @param AssetFileModel $file
	 * @return string
	 */
	private function _getFileSystemPath(AssetFileModel $file)
	{
		$folder = $file->getFolder();
		$fileSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
		return $this->_getSourceFileSystemPath($fileSourceType).$folder->path.$file->filename;
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
		IOHelper::deleteFile($this->_getSourceFileSystemPath().$folder->path.$filename);
	}

	/**
	 * Delete all the generated image transforms for this file.
	 *
	 * @param AssetFileModel $file
	 * @return void
	 */
	protected function _deleteGeneratedImageTransforms(AssetFileModel $file)
	{
		$transformLocations = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);
		$folder = $file->getFolder();
		foreach ($transformLocations as $location)
		{
			IOHelper::deleteFile($this->_getSourceFileSystemPath().$folder->path.$location.'/'.$file->filename);
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

		$newServerPath = $this->_getSourceFileSystemPath().$targetFolder->path.$fileName;

		$conflictingRecord = craft()->assets->findFile(array(
			'folderId' => $targetFolder->id,
			'filename' => $fileName
		));

		$conflict = !$overwrite && (IOHelper::fileExists($newServerPath) || (!craft()->assets->isMergeInProgress() && is_object($conflictingRecord)));

		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->_getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		if (!IOHelper::move($this->_getFileSystemPath($file), $newServerPath))
		{
			$response = new AssetOperationResponseModel();
			return $response->setError(Craft::t("Could not save the file"));
		}

		if ($file->kind == 'image')
		{
			$this->_deleteGeneratedThumbnails($file);

			// Move transforms
			$transforms = craft()->assetTransforms->getGeneratedTransformLocationsForFile($file);
			$baseFromPath = $this->_getSourceFileSystemPath().$file->getFolder()->path;
			$baseToPath = $this->_getSourceFileSystemPath().$targetFolder->path;

			foreach ($transforms as $location)
			{
				if (IOHelper::fileExists($baseFromPath.$location.'/'.$file->filename))
				{
					IOHelper::ensureFolderExists($baseToPath.$location);
					IOHelper::move($baseFromPath.$location.'/'.$file->filename, $baseToPath.$location.'/'.$fileName);
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
		return IOHelper::folderExists($this->_getSourceFileSystemPath() . $parentFolder->path . $folderName);
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
		if (!IOHelper::isWritable($this->_getSourceFileSystemPath() . $parentFolder->path))
		{
			return false;
		}
		return IOHelper::createFolder($this->_getSourceFileSystemPath() . $parentFolder->path . $folderName, IOHelper::getWritableFolderPermissions());
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
		$newFullPath = IOHelper::getParentFolderPath($folder->path).$newName.'/';

		return IOHelper::rename($this->_getSourceFileSystemPath().$folder->path, $this->_getSourceFileSystemPath().$newFullPath);
	}

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @return boolean
	 */
	protected function _deleteSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		return IOHelper::deleteFolder($this->_getSourceFileSystemPath().$parentFolder->path.$folderName);
	}

	/**
	 * Determines if a file can be moved internally from original source.
	 *
	 * @param BaseAssetSourceType $originalSource
	 * @return mixed
	 */
	protected function canMoveFileFrom(BaseAssetSourceType $originalSource)
	{
		return $originalSource->isSourceLocal();
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
		$fileFolder = $file->getFolder();
		$basePath = $this->_getSourceFileSystemPath().$fileFolder->path;
		IOHelper::copyFile($basePath.$source.'/'.$file->filename, $basePath.$target.'/'.$file->filename);
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
		return IOHelper::fileExists($this->_getImageServerPath($file, $location));
	}

	/**
	 * Return the source's base URL.
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		$url = $this->getSettings()->url;
		return craft()->config->parseEnvironmentString($url);
	}

	/**
	 * Returns the source's base server path.
	 *
	 * @return string
	 */
	public function getBasePath()
	{
		$path = $this->getSettings()->path;
		return craft()->config->parseEnvironmentString($path);
	}
}
