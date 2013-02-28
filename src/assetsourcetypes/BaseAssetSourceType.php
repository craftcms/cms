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
	 * Get the timestamp of when a file transformation was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $transformationHandle
	 * @return mixed
	 */
	public abstract function getTimeTransformationModified(AssetFileModel $fileModel, $transformationHandle);

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
	 * Put an image transformation for the File and handle using the provided path to the source image.
	 *
	 * @param AssetFileModel $fileModel
	 * @param $handle
	 * @param $sourceImage
	 * @return mixed
	 */
	abstract public function putImageTransformation(AssetFileModel $fileModel, $handle, $sourceImage);

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
	 * @param AssetFileModel $file
	 */
	abstract protected function _deleteSourceFile(AssetFileModel $file);

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel $file
	 * @param AssetFolderModel $targetFolder
	 * @param string $fileName
	 * @param string $userResponse Conflict resolution response
	 * @return AssetOperationResponseModel
	 */
	abstract protected function _moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $userResponse = '');

	/**
	 * Delete generated image transformations for a File.
	 *
	 * @param AssetFileModel $file
	 * @return mixed
	 */
	abstract protected function _deleteGeneratedImageTransformations(AssetFileModel $file);

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
	 * @param AssetFolderModel $folder
	 * @return boolean
	 */
	abstract protected function _deleteSourceFolder(AssetFolderModel $folder);

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
	 * Return a result object for prompting the user about filename conflicts.
	 *
	 * @param string $fileName the cause of all trouble
	 * @return object
	 */
	protected function _getUserPromptOptions($fileName)
	{
		return (object) array(
			'message' => Craft::t('File "{file}" already exists at target location', array('file' => $fileName)),
			'choices' => array(
				array('value' => AssetsHelper::ActionKeepBoth, 'title' => Craft::t('Rename the new file and keep both')),
				array('value' => AssetsHelper::ActionReplace, 'title' => Craft::t('Replace the existing file')),
				array('value' => AssetsHelper::ActionCancel, 'title' => Craft::t('Keep the original file'))
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
		$uploader = new \qqFileUploader();

		// Make sure a file was uploaded
		if (! $uploader->file)
		{
			throw new Exception(Craft::t('No file was uploaded'));
		}

		$size = $uploader->file->getSize();

		// Make sure the file isn't empty
		if (!$size)
		{
			throw new Exception(Craft::t('Uploaded file was empty'));
		}

		// Save the file to a temp location and pass this on to the source type implementation
		$filePath = AssetsHelper::getTempFilePath(IOHelper::getExtension($uploader->file->getName()));
		$uploader->file->save($filePath);


		$response = $this->_insertFileInFolder($folder, $filePath, $uploader->file->getName());

		// Naming conflict. create a new file and ask the user what to do with it
		if ($response->isConflict())
		{
			$newFileName = $this->_getNameReplacement($folder, $uploader->file->getName());
			$conflictResponse = $response;
			$response = $this->_insertFileInFolder($folder, $filePath, $newFileName);
		}

		if ($response->isSuccess())
		{
			$filename = pathinfo($response->getDataItem('filePath'), PATHINFO_BASENAME);

			$fileModel = new AssetFileModel();
			$fileModel->sourceId = $this->model->id;
			$fileModel->folderId = $folder->id;
			$fileModel->filename = pathinfo($filename, PATHINFO_BASENAME);
			$fileModel->kind = IOHelper::getFileKind(pathinfo($filename, PATHINFO_EXTENSION));
			$fileModel->size = filesize($filePath);
			$fileModel->dateModified = IOHelper::getLastTimeModified($filePath);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($filePath);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			craft()->assets->storeFile($fileModel);

			if (!$this->isSourceLocal())
			{
				// Store copy locally for all sorts of operations.
				IOHelper::copyFile($filePath, craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.pathinfo($fileModel, PATHINFO_EXTENSION));
			}

			craft()->assetTransformations->updateTransformations($fileModel, array_keys(craft()->assetTransformations->getAssetTransformations()));

			// Check if we stored a conflict response originally - send that back then.
			if (isset($conflictResponse))
			{
				$response = $conflictResponse;
				$response->setDataItem('additionalInfo', $folder->id.':'.$fileModel->id);
				$response->setDataItem('newFileId', $fileModel->id);
			}

			$response->setDataItem('fileId', $fileModel->id);
		}

		IOHelper::deleteFile($filePath);

		// Prevent sensitive information leak. Just in case.
		$response->deleteDataItem('filePath');

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
					$this->deleteFile($fileToDelete);
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

			if (!$this->isSourceLocal())
			{
				// Store copy locally for all sorts of operations.
				IOHelper::copyFile($localCopy, craft()->path->getAssetsImageSourcePath().$file->id.'.'.pathinfo($file, PATHINFO_EXTENSION));
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
			$response->setSuccess();
			return $response;
		}

		// If this is a revisited conflict, perform the appropriate actions
		if (!empty($action))
		{
			switch ($action)
			{
				case AssetsHelper::ActionReplace:
				{
					$fileToDelete = craft()->assets->findFile(array('folderId' => $targetFolder->id, 'filename' => $filename));
					$this->deleteFile($fileToDelete);
					break;
				}

				case AssetsHelper::ActionKeepBoth:
				{
					$filename = $this->_getNameReplacement($targetFolder, $filename);
					break;
				}
			}
		}

		$response = $this->_moveSourceFile($file, $targetFolder, $filename, $action);
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
			$this->_deleteSourceFile($oldFile);

			// For remote sources, fetch the source image and move it in the old one's place
			if (!$this->isSourceLocal())
			{
				$localCopy = $this->getLocalCopy($replaceWith);
				IOHelper::copyFile($localCopy, craft()->path->getAssetsImageSourcePath().$oldFile->id.'.'.pathinfo($oldFile, PATHINFO_EXTENSION));
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
		$this->finalizeOutgoingTransfer($file);

		IOHelper::deleteFile(craft()->path->getAssetsImageSourcePath().$file->id.'.'.IOHelper::getExtension($file->filename));

		craft()->assets->deleteFileRecord($file->id);

		$response = new AssetOperationResponseModel();
		$response->setSuccess();

		return $response;
	}

	/**
	 * Finalize an outgoing transfer for a file.
	 *
	 * @param AssetFileModel $file
	 */
	public function finalizeOutgoingTransfer(AssetFileModel $file)
	{
		$this->_deleteGeneratedImageTransformations($file);
		$this->_deleteGeneratedThumbnails($file);
		$this->_deleteSourceFile($file);
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
		$response->setSuccess();
		$response->setDataItem('folderId', $folderId);
		$response->setDataItem('parentId', $parentFolder->id);
		$response->setDataItem('folderName', $folderName);

		return $response;
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
			throw new Exception(Craft::t("Cannot rename folder \"{folder}\"!", array('folder' => $folder->name)));
		}
		if ($this->_sourceFolderExists($parentFolder, $newName))
		{
			throw new Exception(Craft::t("Folder \"{folder}\" already exists there.", array('folder' => $newName)));
		}

		// Try to rename the folder in the source
		if (!$this->_renameSourceFolder($folder, $newName))
		{
			throw new Exception(Craft::t("Cannot rename folder \"{folder}\"!", array('folder' => $folder->name)));
		}

		$oldFullPath = $folder->fullPath;
		$newFullPath = $this->_getParentFullPath($folder->fullPath).$newName.'/';

		// Find all folders with affected fullPaths and update them.
		$folders = craft()->assets->findChildFolders($folder);
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
		$response->setSuccess();
		$response->setDataItem('newName', $newName);
		return $response;
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
		$files = craft()->assets->findFiles(array(
			'folderId' => $folder->id
		));

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

		$this->_deleteSourceFolder($folder);

		craft()->assets->deleteFolderRecord($folder->id);

		$response = new AssetOperationResponseModel();
		$response->setSuccess();
		return $response;
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
}
