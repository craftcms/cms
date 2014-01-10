<?php
namespace Craft;

/**
 * Asset source base class
 */
abstract class BaseAssetSourceType extends BaseSavableComponentType
{
	/**
	 * @var bool
	 */
	protected $_isSourceLocal = false;

	/**
	 * @access protected
	 * @var string The type of component this is
	 */
	protected $componentType = 'AssetSourceType';

	/**
	 * Starts an indexing session
	 * @param $sessionId
	 * @return array
	 */
	abstract public function startIndex($sessionId);

	/**
	 * Process an indexing session
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 */
	abstract public function processIndex($sessionId, $offset);

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 * @return mixed
	 */
	public abstract function getImageSourcePath(AssetFileModel $fileModel);

	/**
	 * Get the timestamp of when a file transform was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $transformLocation
	 * @return mixed
	 */
	public abstract function getTimeTransformModified(AssetFileModel $fileModel, $transformLocation);

	/**
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filePath
	 * @param $fileName
	 * @return AssetOperationResponseModel
	 * @throws Exception
	 */
	abstract protected function _insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName);

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @return mixed
	 */
	abstract protected function _getNameReplacement(AssetFolderModel $folder, $fileName);

	/**
	 * Put an image transform for the File and handle using the provided path to the source image.
	 *
	 * @param AssetFileModel $fileModel
	 * @param $handle
	 * @param $sourceImage
	 * @return mixed
	 */
	abstract public function putImageTransform(AssetFileModel $fileModel, $handle, $sourceImage);

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param AssetFileModel $file
	 * @return mixed
	 */
	abstract public function getLocalCopy(AssetFileModel $file);

	/**
	 * Delete just the file inside of a source for an Assets File.
	 *
	 * @param AssetFolderModel $folder
	 * @param                  $filename
	 * @return
	 */
	abstract protected function _deleteSourceFile(AssetFolderModel $folder, $filename);

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel $file
	 * @param AssetFolderModel $targetFolder
	 * @param string $fileName
	 * @param bool $overwrite if True, will overwrite target destination
	 * @return AssetOperationResponseModel
	 */
	abstract protected function _moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $overwrite = false);

	/**
	 * Delete generated image transforms for a File.
	 *
	 * @param AssetFileModel $file
	 * @return mixed
	 */
	abstract protected function _deleteGeneratedImageTransforms(AssetFileModel $file);

	/**
	 * Return TRUE if a physical folder exists.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param $folderName
	 * @return boolean
	 */
	abstract protected function _sourceFolderExists(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Create a physical folder, return TRUE on success.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param $folderName
	 * @return boolean
	 */
	abstract protected function _createSourceFolder(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param $folderName
	 * @return boolean
	 */
	abstract protected function _deleteSourceFolder(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Rename a source folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $newName
	 * @return boolean
	 */
	abstract protected function _renameSourceFolder(AssetFolderModel $folder, $newName);

	/**
	 * Determines if a file can be moved internally from original source.
	 *
	 * @param BaseAssetSourceType $originalSource
	 * @return mixed
	 */
	abstract protected function canMoveFileFrom(BaseAssetSourceType $originalSource);

	/**
	 * Copy a transform for a file from source location to target location.
	 *
	 * @param AssetFileModel $file
	 * @param $source
	 * @param $target
	 * @return mixed
	 */
	abstract public function copyTransform(AssetFileModel $file, $source, $target);

	/**
	 * Return true if a transform exists at the location for a file.
	 *
	 * @param AssetFileModel $file
	 * @param $location
	 * @return mixed
	 */
	abstract public function transformExists(AssetFileModel $file, $location);

	/**
	 * Return the source's base URL.
	 *
	 * @return string
	 */
	abstract public function getBaseUrl();

	/**
	 * Return a result object for prompting the user about filename conflicts.
	 *
	 * @param string $fileName the cause of all trouble
	 * @return object
	 */
	protected function _getUserPromptOptions($fileName)
	{
		return (object) array(
			'message' => Craft::t('File “{file}” already exists at target location', array('file' => $fileName)),
			'choices' => array(
				array('value' => AssetsHelper::ActionKeepBoth, 'title' => Craft::t('Rename the new file and keep both')),
				array('value' => AssetsHelper::ActionReplace, 'title' => Craft::t('Replace the existing file')),
				array('value' => AssetsHelper::ActionCancel, 'title' => Craft::t('Keep the original file'))
			)
		);
	}

	/**
	 * Return a result array for prompting the user about folder conflicts.
	 *
	 * @param string $folderName the caused of all trouble
	 * @param int $folderId
	 * @return object
	 */
	protected function _getUserFolderPromptOptions($folderName, $folderId)
	{
		return array(
			'message' => Craft::t('Folder “{folder}” already exists at target location', array('folder' => $folderName)),
			'file_name' => $folderId,
			'choices' => array(
				array('value' => AssetsHelper::ActionReplace, 'title' => Craft::t('Replace the existing folder')),
				array('value' => AssetsHelper::ActionCancel, 'title' => Craft::t('Cancel the folder move.'))
			)
		);
	}

	/**
	 * Returns true if this is a valid source. Used for type-specific validations
	 * @return array
	 */
	public function getSourceErrors()
	{
		return array();
	}

	/**
	 * Upload a file.
	 *
	 * @param AssetFolderModel $folder
	 * @return object
	 * @throws Exception
	 */
	public function uploadFile($folder)
	{
		// Upload the file and drop it in the temporary folder
		$file = $_FILES['assets-upload'];

		// Make sure a file was uploaded
		if (empty($file['name']))
		{
			throw new Exception(Craft::t('No file was uploaded'));
		}

		$size = $file['size'];

		// Make sure the file isn't empty
		if (!$size)
		{
			throw new Exception(Craft::t('Uploaded file was empty'));
		}

		$fileName = IOHelper::cleanFilename($file['name']);

		// Save the file to a temp location and pass this on to the source type implementation
		$filePath = AssetsHelper::getTempFilePath(IOHelper::getExtension($fileName));
		move_uploaded_file($file['tmp_name'], $filePath);

		$response = $this->insertFileByPath($filePath, $folder, $fileName);

		IOHelper::deleteFile($filePath);

		// Prevent sensitive information leak. Just in case.
		$response->deleteDataItem('filePath');

		return $response;
	}

	/**
	 * Insert a file into a folder by it's local path.
	 *
	 * @param $localFilePath
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @param bool $preventConflicts if set to true, will ensure that a conflict is not encountered by checking the file name prior insertion.
	 * @return AssetOperationResponseModel
	 */
	public function insertFileByPath($localFilePath, AssetFolderModel $folder, $fileName, $preventConflicts = false)
	{
		// We hate Javascript and PHP in our image files.
		if (IOHelper::getFileKind(IOHelper::getExtension($localFilePath)) == 'image' && ImageHelper::isImageManipulatable(IOHelper::getExtension($localFilePath)))
		{
			craft()->images->cleanImage($localFilePath);
		}

		if ($preventConflicts)
		{
			$newFileName = $this->_getNameReplacement($folder, $fileName);
			$response = $this->_insertFileInFolder($folder, $localFilePath, $newFileName);
		}
		else
		{
			$response = $this->_insertFileInFolder($folder, $localFilePath, $fileName);

			// Naming conflict. create a new file and ask the user what to do with it
			if ($response->isConflict())
			{
				$newFileName = $this->_getNameReplacement($folder, $fileName);
				$conflictResponse = $response;
				$response = $this->_insertFileInFolder($folder, $localFilePath, $newFileName);
			}
		}

		if ($response->isSuccess())
		{
			$filename = IOHelper::getFileName($response->getDataItem('filePath'));

			$fileModel = new AssetFileModel();
			$fileModel->sourceId = $this->model->id;
			$fileModel->folderId = $folder->id;
			$fileModel->filename = IOHelper::getFileName($filename);
			$fileModel->kind = IOHelper::getFileKind(IOHelper::getExtension($filename));
			$fileModel->size = filesize($localFilePath);
			$fileModel->dateModified = IOHelper::getLastTimeModified($localFilePath);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($localFilePath);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			craft()->assets->storeFile($fileModel);

			if (!$this->isSourceLocal() && $fileModel->kind == 'image')
			{
				// Store copy locally for all sorts of operations.
				IOHelper::copyFile($localFilePath, craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.IOHelper::getExtension($fileModel->filename));
			}

			// Check if we stored a conflict response originally - send that back then.
			if (isset($conflictResponse))
			{
				$response = $conflictResponse->setDataItem('additionalInfo', $folder->id.':'.$fileModel->id)->setDataItem('newFileId', $fileModel->id);
			}

			$response->setDataItem('fileId', $fileModel->id);
		}

		return $response;
	}

	/**
	 * Transfer a file into the source.
	 *
	 * @param string $localCopy
	 * @param AssetFolderModel $folder
	 * @param AssetFileModel $file
	 * @param $action
	 * @return AssetOperationResponseModel
	 */
	public function transferFileIntoSource($localCopy, AssetFolderModel $folder, AssetFileModel $file, $action)
	{
		$filename = IOHelper::cleanFilename($file->filename);

		if (!empty($action))
		{
			switch ($action)
			{
				case AssetsHelper::ActionReplace:
				{
					$fileToDelete = craft()->assets->findFile(array('folderId' => $folder->id, 'filename' => $filename));
					if ($fileToDelete)
					{
						$this->deleteFile($fileToDelete);
					}
					else
					{
						$this->_deleteSourceFile($folder, $filename);
					}
					break;
				}

				case AssetsHelper::ActionKeepBoth:
				{
					$filename = $this->_getNameReplacement($folder, $filename);
					break;
				}
			}
		}

		$response = $this->_insertFileInFolder($folder, $localCopy, $filename);
		if ($response->isSuccess())
		{
			$file->folderId = $folder->id;
			$file->filename = $filename;
			$file->sourceId = $folder->sourceId;
			craft()->assets->storeFile($file);

			if (!$this->isSourceLocal() && $file->kind == "image")
			{
				// Store copy locally for all sorts of operations.
				IOHelper::copyFile($localCopy, craft()->path->getAssetsImageSourcePath().$file->id.'.'.IOHelper::getExtension($file));
			}
		}

		return $response;
	}

	/**
	 * Move file from one path to another if it's possible. Return false on failure.
	 *
	 * @param BaseAssetSourceType $originalSource
	 * @param AssetFileModel $file
	 * @param AssetFolderModel $targetFolder
	 * @param $filename
	 * @param $action
	 * @return bool|AssetOperationResponseModel
	 */
	public function moveFileInsideSource(BaseAssetSourceType $originalSource, AssetFileModel $file, AssetFolderModel $targetFolder, $filename, $action = '')
	{
		if (!$this->canMoveFileFrom($originalSource))
		{
			return false;
		}

		if ($file->folderId == $targetFolder->id && $filename == $file->filename)
		{
			$response = new AssetOperationResponseModel();
			return $response->setSuccess();
		}

		// If this is a revisited conflict, perform the appropriate actions
		if (!empty($action))
		{
			switch ($action)
			{
				case AssetsHelper::ActionReplace:
				{
					$fileToDelete = craft()->assets->findFile(array('folderId' => $targetFolder->id, 'filename' => $filename));
					if ($fileToDelete)
					{
						$this->deleteFile($fileToDelete);
						$this->_purgeCachedSourceFile($targetFolder, $filename);
					}
					else
					{
						$this->_deleteSourceFile($targetFolder, $filename);
						$this->_purgeCachedSourceFile($targetFolder, $filename);
					}
					break;
				}

				case AssetsHelper::ActionKeepBoth:
				{
					$filename = $this->_getNameReplacement($targetFolder, $filename);
					break;
				}
			}
		}

		// If it's the same folder and the case is changing (if it's not, it's covered above), overwrite the file.
		if ($file->folderId == $targetFolder->id && mb_strtolower($filename) == mb_strtolower($file->filename))
		{
			$overwrite = true;
		}
		else
		{
			$overwrite = false;
		}

		$response = $this->_moveSourceFile($file, $targetFolder, $filename, $overwrite);
		if ($response->isSuccess())
		{
			$file->folderId = $targetFolder->id;
			$file->filename = $filename;
			$file->sourceId = $targetFolder->sourceId;
			craft()->assets->storeFile($file);
		}

		return $response;
	}

	/**
	 * Ensure a folder entry exists in the DB for the full path and return it's id.
	 *
	 * @param $fullPath
	 * @return int
	 */
	protected function _ensureFolderByFulPath($fullPath)
	{
		$parameters = new FolderCriteriaModel(array(
			'fullPath' => $fullPath,
			'sourceId' => $this->model->id
		));

		$folderModel = craft()->assets->findFolder($parameters);

		// If we don't have a folder matching these, create a new one
		if (is_null($folderModel))
		{
			$parts = explode('/', rtrim($fullPath, '/'));
			$folderName = array_pop($parts);

			if (empty($parts))
			{
				// Looking for a top level folder, apparently.
				$parameters->fullPath = "";
				$parameters->parentId = FolderCriteriaModel::AssetsNoParent;
			}
			else
			{
				$parameters->fullPath = join('/', $parts) . '/';
			}

			// Look up the parent folder
			$parentFolder = craft()->assets->findFolder($parameters);
			if (is_null($parentFolder))
			{
				$parentId = FolderCriteriaModel::AssetsNoParent;
			}
			else
			{
				$parentId = $parentFolder->id;
			}

			$folderModel = new AssetFolderModel();
			$folderModel->sourceId = $this->model->id;
			$folderModel->parentId = $parentId;
			$folderModel->name = $folderName;
			$folderModel->fullPath = $fullPath;

			return craft()->assets->storeFolder($folderModel);
		}
		else
		{
			return $folderModel->id;
		}
	}

	/**
	 * Return a list of missing folders, when comparing the full folder list for this source against the provided list.
	 *
	 * @param array $folderList
	 * @return array
	 */
	protected function _getMissingFolders(array $folderList)
	{
		// Figure out the obsolete records for folders
		$missingFolders = array();

		$allFolders = craft()->assets->findFolders(array(
			'sourceId' => $this->model->id
		));

		foreach ($allFolders as $folderModel)
		{
			if (!isset($folderList[$folderModel->id]))
			{
				$missingFolders[$folderModel->id] = $this->model->name . '/' . $folderModel->fullPath;
			}
		}

		return $missingFolders;
	}

	/**
	 * @param $uriPath
	 * @return AssetFileModel|bool|null
	 */
	protected function _indexFile($uriPath)
	{
		$extension = IOHelper::getExtension($uriPath);

		if (IOHelper::isExtensionAllowed($extension))
		{
			$parts = explode('/', $uriPath);
			$fileName = array_pop($parts);

			$searchFullPath = join('/', $parts) . (empty($parts) ? '' : '/');

			if (empty($searchFullPath))
			{
				$parentId = FolderCriteriaModel::AssetsNoParent;
			}
			else
			{
				$parentId = false;
			}

			$parentFolder = craft()->assets->findFolder(array(
				'sourceId' => $this->model->id,
				'fullPath' => $searchFullPath,
				'parentId' => $parentId
			));

			if (empty($parentFolder))
			{
				return false;
			}

			$folderId = $parentFolder->id;

			$fileModel = craft()->assets->findFile(array(
				'folderId' => $folderId,
				'filename' => $fileName
			));

			if (is_null($fileModel))
			{
				$fileModel = new AssetFileModel();
				$fileModel->sourceId = $this->model->id;
				$fileModel->folderId = $folderId;
				$fileModel->filename = $fileName;
				$fileModel->kind = IOHelper::getFileKind($extension);
				craft()->assets->storeFile($fileModel);
			}

			return $fileModel;
		}

		return false;
	}

	/**
	 * Replace physical file.
	 *
	 * @param AssetFileModel $oldFile
	 * @param AssetFileModel $replaceWith
	 */
	public function replaceFile(AssetFileModel $oldFile, AssetFileModel $replaceWith)
	{

		if ($oldFile->kind == 'image')
		{
			$this->_deleteGeneratedThumbnails($oldFile);
			$this->_deleteSourceFile($oldFile->getFolder(), $oldFile->filename);
			$this->_purgeCachedSourceFile($oldFile->getFolder(), $oldFile->filename);

			// For remote sources, fetch the source image and move it in the old one's place
			if (!$this->isSourceLocal())
			{
				$localCopy = $this->getLocalCopy($replaceWith);
				if ($oldFile->kind == "image")
				{
					IOHelper::copyFile($localCopy, craft()->path->getAssetsImageSourcePath().$oldFile->id.'.'.IOHelper::getExtension($oldFile));
				}
				IOHelper::deleteFile($localCopy);
			}
		}

		$this->_moveSourceFile($replaceWith, craft()->assets->getFolderById($oldFile->folderId), $oldFile->filename);

		// Update file info
		$oldFile->width = $replaceWith->width;
		$oldFile->height = $replaceWith->height;
		$oldFile->size = $replaceWith->size;
		$oldFile->dateModified = $replaceWith->dateModified;

		craft()->assets->storeFile($oldFile);
	}

	/**
	 * Delete all the generated images for this file.
	 *
	 * @param AssetFileModel $file
	 */
	protected function _deleteGeneratedThumbnails(AssetFileModel $file)
	{
		$thumbFolders = IOHelper::getFolderContents(craft()->path->getAssetsThumbsPath());
		foreach ($thumbFolders as $folder)
		{
			if (is_dir($folder))
			{
				IOHelper::deleteFile($folder.'/'.$file->id.'.'.IOHelper::getExtension($file->filename));
			}
		}
	}

	/**
	 * Delete a file.
	 *
	 * @param AssetFileModel $file
	 * @return AssetOperationResponseModel
	 */
	public function deleteFile(AssetFileModel $file)
	{
		// Delete all the created images, such as transforms, thumbnails
		$this->deleteCreatedImages($file);
		craft()->assetTransforms->deleteTransformRecordsByFileId($file->id);

		$filePath = craft()->path->getAssetsImageSourcePath().$file->id.'.'.IOHelper::getExtension($file->filename);
		if (IOHelper::fileExists($filePath))
		{
			IOHelper::deleteFile($filePath);
		}

		// Delete DB record and the file itself.
		craft()->elements->deleteElementById($file->id);
		$this->_deleteSourceFile($file->getFolder(), $file->filename);

		$response = new AssetOperationResponseModel();
		return $response->setSuccess();
	}

	/**
	 * Finalize an outgoing transfer for a file.
	 *
	 * @param AssetFileModel $file
	 */
	public function deleteCreatedImages(AssetFileModel $file)
	{
		$this->_deleteGeneratedImageTransforms($file);
		$this->_deleteGeneratedThumbnails($file);
	}

	/**
	 * Create a subfolder.
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param $folderName
	 * @return AssetOperationResponseModel
	 * @throws Exception
	 */
	public function createFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$folderName = IOHelper::cleanFilename($folderName);

		// If folder exists in DB or physically, bail out
		if (craft()->assets->findFolder(array('parentId' => $parentFolder->id, 'name' => $folderName))
			|| $this->_sourceFolderExists($parentFolder, $folderName))
		{
			throw new Exception(Craft::t('A folder already exists with that name!'));
		}

		if ( !$this->_createSourceFolder($parentFolder, $folderName))
		{
			throw new Exception(Craft::t('There was an error while creating the folder.'));
		}

		$newFolder = new AssetFolderModel();
		$newFolder->sourceId = $parentFolder->sourceId;
		$newFolder->parentId = $parentFolder->id;
		$newFolder->name = $folderName;
		$newFolder->fullPath = $parentFolder->fullPath.$folderName.'/';

		$folderId = craft()->assets->storeFolder($newFolder);

		$response = new AssetOperationResponseModel();
		return $response->setSuccess()
				->setDataItem('folderId', $folderId)
		    	->setDataItem('parentId', $parentFolder->id)
		    	->setDataItem('folderName', $folderName);
	}

	/**
	 * Rename a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param                  $newName
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function renameFolder(AssetFolderModel $folder, $newName)
	{
		$parentFolder = craft()->assets->getFolderById($folder->parentId);

		if (!$parentFolder)
		{
			throw new Exception(Craft::t("Cannot rename folder “{folder}”!", array('folder' => $folder->name)));
		}

		// Allow this for changing the case
		if (!(mb_strtolower($newName) == mb_strtolower($folder->name)) && $this->_sourceFolderExists($parentFolder, $newName))
		{
			throw new Exception(Craft::t("Folder “{folder}” already exists there.", array('folder' => $newName)));
		}

		// Try to rename the folder in the source
		if (!$this->_renameSourceFolder($folder, $newName))
		{
			throw new Exception(Craft::t("Cannot rename folder “{folder}”!", array('folder' => $folder->name)));
		}

		$oldFullPath = $folder->fullPath;
		$newFullPath = $this->_getParentFullPath($folder->fullPath).$newName.'/';

		// Find all folders with affected fullPaths and update them.
		$folders = craft()->assets->getAllDescendantFolders($folder);
		foreach ($folders as $folderModel)
		{
			$folderModel->fullPath = preg_replace('#^'.$oldFullPath.'#', $newFullPath, $folderModel->fullPath);
			craft()->assets->storeFolder($folderModel);
		}

		// Now change the affected folder
		$folder->name = $newName;
		$folder->fullPath = $newFullPath;
		craft()->assets->storeFolder($folder);

		// All set, Scotty!
		$response = new AssetOperationResponseModel();
		return $response->setSuccess()->setDataItem('newName', $newName);
	}

	/**
	 * @param AssetFolderModel $folder
	 * @param AssetFolderModel $newParentFolder
	 * @param boolean $overwriteTarget if true will overwrite folder, if needed
	 * @return AssetOperationResponseModel
	 */
	public function moveFolder(AssetFolderModel $folder, AssetFolderModel $newParentFolder, $overwriteTarget = false)
	{
		$response = new AssetOperationResponseModel();
		if ($folder->id == $newParentFolder->id)
		{
			return $response->setSuccess();
		}

		$removeFromTree = '';
		if ($this->_sourceFolderExists($newParentFolder, $folder->name))
		{
			if ($overwriteTarget)
			{
				$existingFolder = craft()->assets->findFolder(array('parentId' => $newParentFolder->id, 'name' => $folder->name));
				if ($existingFolder)
				{
					$removeFromTree = $existingFolder->id;
					$this->deleteFolder($existingFolder);
				}
				else
				{
					$this->_deleteSourceFolder($newParentFolder, $folder->name);
				}
			}
			else
			{
				return $response->setPrompt($this->_getUserFolderPromptOptions($folder->name, $folder->id))->setDataItem('folderId', $folder->id);
			}
		}

		$response->setSuccess()->setDataItem('deleteList', array($folder->id))->setDataItem('removeFromTree', $removeFromTree);

		$mirroringData = array('changedFolderIds' => array());
		$this->_mirrorStructure($newParentFolder, $folder, $mirroringData);

		$response->setDataItem('changedFolderIds', $mirroringData['changedFolderIds']);

		$criteria = craft()->elements->getCriteria(ElementType::Asset);
		$criteria->folderId = array_keys(craft()->assets->getAllDescendantFolders($folder));
		$files = $criteria->find();

		$transferList = array();
		foreach ($files as $file)
		{
			$transferList[] = array(
				'fileId' => $file->id,
				'folderId' => $mirroringData['changedFolderIds'][$file->folderId]['newId'],
				'fileName' => $file->filename
			);
		}

		return $response->setDataItem('transferList', $transferList);
	}

	/**
	 * Mirrors a subset of folder tree from one location to other.
	 *
	 * @param AssetFolderModel $newLocation
	 * @param AssetFolderModel $sourceFolder
	 * @param $changedData
	 * @throws Exception
	 */
	private function _mirrorStructure(AssetFolderModel $newLocation, AssetFolderModel $sourceFolder, &$changedData)
	{
		$response = $this->createFolder($newLocation, $sourceFolder->name);

		if ($response->isSuccess())
		{
			$newId = $response->getDataItem('folderId');
			$parentId = $response->getDataItem('parentId');

			$changedData['changedFolderIds'][$sourceFolder->id] = array(
				'newId' => $newId,
				'newParentId' => $parentId
			);

			$newTargetRow = craft()->assets->getFolderById($newId);

			$children = craft()->assets->findFolders(array('parentId' => $sourceFolder->id));
			foreach ($children as $child)
			{
				$this->_mirrorStructure($newTargetRow, $child, $changedData);
			}
		}
		else
		{
			throw new Exception(Craft::t("Failed to successfully mirror folder structure"));
		}
	}

	/**
	 * Delete a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @return AssetOperationResponseModel
	 */
	public function deleteFolder(AssetFolderModel $folder)
	{
		// Get rid of children files
		$criteria = craft()->elements->getCriteria(ElementType::Asset);
		$criteria->folderId = $folder->id;
		$files = $criteria->find();

		foreach ($files as $file)
		{
			$this->deleteFile($file);
		}

		// Delete children folders
		$childFolders = craft()->assets->findFolders(array('parentId' => $folder->id));
		foreach ($childFolders as $childFolder)
		{
			$this->deleteFolder($childFolder);
		}

		$parentFolder = craft()->assets->getFolderById($folder->parentId);
		$this->_deleteSourceFolder($parentFolder, $folder->name);

		craft()->assets->deleteFolderRecord($folder->id);

		$response = new AssetOperationResponseModel();
		return $response->setSuccess();
	}

	/**
	 * Return a parent folder's full path for a full path.
	 *
	 * @param $fullPath
	 * @return string
	 */
	protected function _getParentFullPath($fullPath)
	{
		// Drop the trailing slash and split it by slash
		$parts = explode("/", rtrim($fullPath, "/"));

		// Drop the last part and return the part leading up to it
		array_pop($parts);

		if (empty($parts))
		{
			return '';
		}

		return join("/", $parts) . '/';
	}

	/**
	 * @return boolean
	 */
	public function isSourceLocal()
	{
		return $this->_isSourceLocal;
	}

	/**
	 * Finalize a file transfer between sources for the provided file.
	 *
	 * @param AssetFileModel $file
	 * @return mixed
	 */
	public function finalizeTransfer(AssetFileModel $file)
	{
		$this->_deleteSourceFile($file->getFolder(), $file->filename);
	}

	/**
	 * Purge a file from the Source's cache. Sources that need this should override this method.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filename
	 * @return void
	 */
	protected function _purgeCachedSourceFile(AssetFolderModel $folder, $filename)
	{
		return;
	}

	/**
	 * Return true if the source is a remote source.
	 *
	 * @return bool
	 */
	public function isRemote()
	{
		return false;
	}
}
