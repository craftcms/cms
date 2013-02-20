<?php
namespace Blocks;

/**
 * Local source type class
 */
class LocalAssetSourceType extends BaseAssetSourceType
{
	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Local Folder');
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
		return blx()->templates->render('_components/assetsourcetypes/Local/settings', array(
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
		$settings['path'] = rtrim($settings['path'], '/').'/';
		$settings['url'] = rtrim($settings['url'], '/').'/';

		return $settings;
	}

	/**
	 * Check if the FileSystem path is a writable folder
	 * @return array
	 */
	public function getSourceErrors()
	{
		$errors = array();
		if (!(IOHelper::folderExists($this->_getSourceFileSystemPath()) && IOHelper::isWritable($this->_getSourceFileSystemPath()))) {
			$errors['path'] = Blocks::t("The destination folder doesn't exist or is not writable.");
		}

		return $errors;
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

		$indexedFolderIds[blx()->assetIndexing->ensureTopFolder($this->model)] = true;

		$localPath = $this->_getSourceFileSystemPath();
		$fileList = IOHelper::getFolderContents($localPath, true);

		$fileList = array_filter($fileList, function ($value) use ($localPath)
		{
			$path = substr($value, strlen($localPath));
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

		$offset = 0;
		$total = 0;

		foreach ($fileList as $file)
		{
			if (!preg_match(AssetsHelper::IndexSkipItemsPattern, $file))
			{
				if (is_dir($file))
				{
					$fullPath = rtrim(str_replace($this->_getSourceFileSystemPath(), '', $file), '/') . '/';
					$folderId = $this->_ensureFolderByFulPath($fullPath);
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

					blx()->assetIndexing->storeIndexEntry($indexEntry);
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
	 * @return string
	 */
	private function _getSourceFileSystemPath()
	{
		$path = $this->getSettings()->path;
		$path = realpath($path).'/';
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
		$indexEntryModel = blx()->assetIndexing->getIndexEntry($this->model->id, $sessionId, $offset);

		if (empty($indexEntryModel))
		{
			return false;
		}

		// Make sure we have a trailing slash. Some people love to skip those.
		$uploadPath = $this->_getSourceFileSystemPath();

		$file = $indexEntryModel->uri;

		// This is the part of the path that actually matters
		$uriPath = substr($file, strlen($uploadPath));

		$fileModel = $this->_indexFile($uriPath);

		if ($fileModel)
		{
			blx()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;
			$fileModel->dateModified = IOHelper::getLastTimeModified($indexEntryModel->uri);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($indexEntryModel->uri);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			blx()->assets->storeFile($fileModel);

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
		$targetFolder = $this->_getSourceFileSystemPath() . $folder->fullPath;

		// Make sure the folder is writable
		if (! IOHelper::isWritable($targetFolder))
		{
			throw new Exception(Blocks::t('Target destination is not writable'));
		}

		$fileName = IOHelper::cleanFilename($fileName);

		$targetPath = $targetFolder . $fileName;
		$extension = IOHelper::getExtension($fileName);

		if (!IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Blocks::t('This file type is not allowed'));
		}

		if (IOHelper::fileExists($targetPath))
		{
			$response = new AssetOperationResponseModel();
			$response->setPrompt($this->_getUserPromptOptions($fileName));
			$response->setDataItem('fileName', $fileName);
			return $response;
		}

		if (! IOHelper::copyFile($filePath, $targetPath))
		{
			throw new Exception(Blocks::t('Could not copy file to target destination'));
		}

		IOHelper::changePermissions($targetPath, IOHelper::writableFilePermissions);

		$response = new AssetOperationResponseModel();
		$response->setSuccess();
		$response->setDataItem('filePath', $targetPath);
		return $response;

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
		$fileList = IOHelper::getFolderContents($this->_getSourceFileSystemPath() . $folder->fullPath, false);
		$existingFiles = array();

		foreach ($fileList as $file)
		{
			$existingFiles[pathinfo($file, PATHINFO_BASENAME)] = true;
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
	 * Get the timestamp of when a file transformation was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $transformationHandle
	 * @return mixed
	 */
	public function getTimeTransformationModified(AssetFileModel $fileModel, $transformationHandle)
	{
		$path = $this->_getImageServerPath($fileModel, $transformationHandle);

		if (!IOHelper::fileExists($path))
		{
			return false;
		}

		return IOHelper::getLastTimeModified($path);
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
	 * @param string $handle
	 * @return string
	 */
	private function _getImageServerPath(AssetFileModel $fileModel, $handle = '')
	{
		$targetFolder = $this->_getSourceFileSystemPath().$fileModel->getFolder()->fullPath;
		$targetFolder .= !empty($handle) ? '_'.$handle.'/': '';

		return $targetFolder.$fileModel->filename;
	}

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param AssetFileModel $file
	 * @return mixed
	 */

	protected function _getLocalCopy(AssetFileModel $file)
	{
		$location = AssetsHelper::getTempFilePath();
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
		return $this->_getSourceFileSystemPath().$folder->fullPath.$file->filename;
	}

	/**
	 * Delete just the source file for an Assets File.
	 *
	 * @param AssetFileModel $file
	 * @return void
	 */
	protected function _deleteSourceFile(AssetFileModel $file)
	{
		IOHelper::deleteFile($this->_getFileSystemPath($file));
	}

	/**
	 * Delete all the generated image transformations for this file.
	 *
	 * @param AssetFileModel $file
	 */
	protected function _deleteGeneratedImageTransformations(AssetFileModel $file)
	{
		$folder = $file->getFolder();
		$transformations = blx()->assetTransformations->getAssetTransformations();
		foreach ($transformations as $handle => $transformation)
		{
			IOHelper::deleteFile($this->_getSourceFileSystemPath().$folder->fullPath.'/_'.$handle.'/'.$file->filename);
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

		$newServerPath = $this->_getSourceFileSystemPath().$targetFolder->fullPath.$fileName;

		$conflictingRecord = blx()->assets->findFile(array(
			'folderId' => $targetFolder->id,
			'filename' => $fileName
		));

		$conflict = IOHelper::fileExists($newServerPath) || (!blx()->assets->isMergeInProgress() && is_object($conflictingRecord));
		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			$response->setPrompt($this->_getUserPromptOptions($fileName));
			$response->setDataItem('fileName', $fileName);
			return $response;
		}

		if (!IOHelper::move($this->_getFileSystemPath($file), $newServerPath))
		{
			$response = new AssetOperationResponseModel();
			$response->setError(Blocks::t("Could not save the file"));
			return $response;
		}

		if ($file->kind == 'image')
		{
			$this->_deleteGeneratedThumbnails($file);

			// Move transformations
			$transformations = blx()->assetTransformations->getAssetTransformations();
			$baseFromPath = $this->_getSourceFileSystemPath().$file->getFolder()->fullPath;
			$baseToPath = $this->_getSourceFileSystemPath().$targetFolder->fullPath;

			foreach ($transformations as $handle => $transformation)
			{
				IOHelper::move($baseFromPath.'_'.$handle.'/'.$fileName, $baseToPath.'_'.$handle.'/'.$fileName);
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
		return IOHelper::folderExists($this->_getSourceFileSystemPath() . $parentFolder->fullPath . $folderName);
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
		return IOHelper::createFolder($this->_getSourceFileSystemPath() . $parentFolder->fullPath . $folderName, IOHelper::writableFolderPermissions);
	}

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @return boolean
	 */
	protected function _deleteSourceFolder(AssetFolderModel $folder)
	{
		return IOHelper::deleteFolder($this->_getSourceFileSystemPath().$folder->fullPath);
	}

}
