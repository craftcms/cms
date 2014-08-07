<?php
namespace Craft;

/**
 * Asset source base class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.assetsourcetypes
 * @since     1.0
 */
abstract class BaseAssetSourceType extends BaseSavableComponentType
{
	// Properties
	// =========================================================================

	/**
	 * Whether this is a local source or not. Defaults to false.
	 *
	 * @var bool
	 */
	protected $isSourceLocal = false;

	/**
	 * The type of component this is.
	 *
	 * @var string
	 */
	protected $componentType = 'AssetSourceType';

	// Public Methods
	// =========================================================================

	/**
	 * Starts an indexing session.
	 *
	 * @param string $sessionId The unique session id to keep track of this indexing operation.
	 *
	 * @return array
	 */
	abstract public function startIndex($sessionId);

	/**
	 * Process an indexing session.
	 *
	 * @param string $sessionId The unique session id to keep track of this indexing operation.
	 * @param int $offset    The offset of this index.
	 *
	 * @return mixed
	 */
	abstract public function processIndex($sessionId, $offset);

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel The assetFileModel for the image source path.
	 *
	 * @return mixed
	 */
	abstract public function getImageSourcePath(AssetFileModel $fileModel);

	/**
	 * Get the timestamp of when a file transform was last modified.
	 *
	 * @param AssetFileModel $fileModel The assetFileModel for the timestamp of the last time the transform was modified.
	 * @param string $transformLocation The location of the transform.
	 *
	 * @return mixed
	 */
	abstract public function getTimeTransformModified(AssetFileModel $fileModel, $transformLocation);

	/**
	 * Put an image transform for the File and handle using the provided path to the source image.
	 *
	 * @param AssetFileModel $fileModel   The assetFileModel to put the image transform in.
	 * @param string         $handle      The handle of the transform.
	 * @param string         $sourceImage The source image.
	 *
	 * @return mixed
	 */
	abstract public function putImageTransform(AssetFileModel $fileModel, $handle, $sourceImage);

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param AssetFileModel $file The assetFileModel that has the file to make a copy of.
	 *
	 * @return mixed
	 */
	abstract public function getLocalCopy(AssetFileModel $file);

	/**
	 * Copy a transform for a file from source location to target location.
	 *
	 * @param AssetFileModel $file   The assetFileModel that has the transform to copy.
	 * @param string         $source The source location of the transform.
	 * @param string         $target The destination target of the transform.
	 *
	 * @return mixed
	 */
	abstract public function copyTransform(AssetFileModel $file, $source, $target);

	/**
	 * Return true if a transform exists at the location for a file.
	 *
	 * @param AssetFileModel $file     The assetFileModel to check if a transform exists.
	 * @param string         $location The location of the transform.
	 *
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
	 * Returns true if this is a valid source. Used for type-specific validations.
	 *
	 * @return array
	 */
	public function getSourceErrors()
	{
		return array();
	}

	/**
	 * Upload a file.
	 *
	 * @param AssetFolderModel $folder The assetFolderModel where the file should be uploaded to.
	 *
	 * @throws Exception
	 * @return object
	 */
	public function uploadFile(AssetFolderModel $folder)
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

		// Prevent sensitive information leak. Just in case.
		$response->deleteDataItem('filePath');

		return $response;
	}

	/**
	 * Insert a file into a folder by it's local path.
	 *
	 * @param string           $localFilePath    The local file path of the file to insert.
	 * @param AssetFolderModel $folder           The assetFolderModel where the file should be uploaded to.
	 * @param string           $fileName         The name of the file to insert.
	 * @param bool             $preventConflicts If set to true, will ensure that a conflict is not encountered by checking the file name prior insertion.
	 *
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
			$newFileName = $this->getNameReplacement($folder, $fileName);
			$response = $this->insertFileInFolder($folder, $localFilePath, $newFileName);
		}
		else
		{
			$response = $this->insertFileInFolder($folder, $localFilePath, $fileName);

			// Naming conflict. create a new file and ask the user what to do with it
			if ($response->isConflict())
			{
				$newFileName = $this->getNameReplacement($folder, $fileName);
				$conflictResponse = $response;
				$response = $this->insertFileInFolder($folder, $localFilePath, $newFileName);
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
				craft()->assetTransforms->storeLocalSource($localFilePath, craft()->path->getAssetsImageSourcePath().$fileModel->id.'.'.IOHelper::getExtension($fileModel->filename));
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
	 * @todo: Refactor this and moveFileInsideSource method - a lot of duplicate code.
	 *
	 * @param string           $localCopy The local copy of the file to transfer.
	 * @param AssetFolderModel $folder    The assetFolderModel that contains the file to transfer.
	 * @param AssetFileModel   $file      The assetFileModel that represents the file to transfer.
	 * @param string           $action    The action to perform during the transfer.
	 *
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
					$fileToReplace = craft()->assets->findFile(array('folderId' => $folder->id, 'filename' => $filename));
					if ($fileToReplace)
					{
						$this->mergeFile($file, $fileToReplace);
					}
					else
					{
						$this->deleteSourceFile($folder, $filename);
					}
					break;
				}

				case AssetsHelper::ActionKeepBoth:
				{
					$filename = $this->getNameReplacement($folder, $filename);
					break;
				}
			}
		}

		$response = $this->insertFileInFolder($folder, $localCopy, $filename);
		if ($response->isSuccess())
		{
			$file->folderId = $folder->id;
			$file->filename = $filename;
			$file->sourceId = $folder->sourceId;
			craft()->assets->storeFile($file);

			if (!$this->isSourceLocal() && $file->kind == "image")
			{
				// Store copy locally for all sorts of operations.
				craft()->assetTransforms->storeLocalSource($localCopy, craft()->path->getAssetsImageSourcePath().$file->id.'.'.IOHelper::getExtension($file->filename));
			}
		}

		return $response;
	}

	/**
	 * Move file from one path to another if it's possible. Return false on failure.
	 *
	 * @param BaseAssetSourceType $originalSource The original source of the file being moved.
	 * @param AssetFileModel $file                The assetFileModel representing the file to move.
	 * @param AssetFolderModel $targetFolder      The assetFolderModel representing the target folder.
	 * @param string $filename                    The file name of the file to move.
	 * @param string $action                      The action to perform during the file move.
	 *
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

		$mergeFiles = false;

		// If this is a revisited conflict, perform the appropriate actions
		if (!empty($action))
		{
			switch ($action)
			{
				case AssetsHelper::ActionReplace:
				{
					$fileToReplace = craft()->assets->findFile(array('folderId' => $targetFolder->id, 'filename' => $filename));
					if ($fileToReplace)
					{
						$this->mergeFile($file, $fileToReplace);
						$this->purgeCachedSourceFile($targetFolder, $filename);
						$mergeFiles = true;
					}
					else
					{
						$this->deleteSourceFile($targetFolder, $filename);
						$this->purgeCachedSourceFile($targetFolder, $filename);
					}
					break;
				}

				case AssetsHelper::ActionKeepBoth:
				{
					$filename = $this->getNameReplacement($targetFolder, $filename);
					break;
				}
			}
		}

		// If it's the same folder and the case is changing (if it's not, it's covered above), overwrite the file.
		if ($file->folderId == $targetFolder->id && StringHelper::toLowerCase($filename) == StringHelper::toLowerCase($file->filename))
		{
			$overwrite = true;
		}
		else
		{
			$overwrite = false || $mergeFiles;
		}

		$response = $this->moveSourceFile($file, $targetFolder, $filename, $overwrite);

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
	 * Replace physical file.
	 *
	 * @param AssetFileModel $oldFile     The assetFileModel representing the original file.
	 * @param AssetFileModel $replaceWith The assetFileModel representing the new file.
	 *
	 * @return null
	 */
	public function replaceFile(AssetFileModel $oldFile, AssetFileModel $replaceWith)
	{
		if ($oldFile->kind == 'image')
		{
			$this->deleteGeneratedThumbnails($oldFile);
			$this->deleteSourceFile($oldFile->getFolder(), $oldFile->filename);
			$this->purgeCachedSourceFile($oldFile->getFolder(), $oldFile->filename);

			// For remote sources, fetch the source image and move it in the old one's place
			if (!$this->isSourceLocal())
			{
				$localCopy = $this->getLocalCopy($replaceWith);
				if ($oldFile->kind == "image")
				{
					IOHelper::copyFile($localCopy, craft()->path->getAssetsImageSourcePath().$oldFile->id.'.'.IOHelper::getExtension($oldFile->filename));
				}
				IOHelper::deleteFile($localCopy);
			}
		}

		$this->moveSourceFile($replaceWith, craft()->assets->getFolderById($oldFile->folderId), $oldFile->filename, true);

		// Update file info
		$oldFile->width = $replaceWith->width;
		$oldFile->height = $replaceWith->height;
		$oldFile->size = $replaceWith->size;
		$oldFile->dateModified = $replaceWith->dateModified;

		craft()->assets->storeFile($oldFile);
	}

	/**
	 * Delete a file.
	 *
	 * @param AssetFileModel $file The assetFileModel representing the file to delete.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function deleteFile(AssetFileModel $file)
	{
		$this->deleteTransformData($file);

		// Delete DB record and the file itself.
		craft()->elements->deleteElementById($file->id);

		$this->deleteSourceFile($file->getFolder(), $file->filename);

		$response = new AssetOperationResponseModel();
		return $response->setSuccess();
	}

	/**
	 * Merge a file.
	 *
	 * @param AssetFileModel $sourceFile The assetFileModel representing the file being merged.
	 * @param AssetFileModel $targetFile The assetFileModel representing the file that is being merged into.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function mergeFile(AssetFileModel $sourceFile, AssetFileModel $targetFile)
	{
		$this->deleteTransformData($targetFile);

		// Delete DB record and the file itself.
		craft()->elements->mergeElementsByIds($targetFile->id, $sourceFile->id);

		$this->deleteSourceFile($targetFile->getFolder(), $targetFile->filename);

		$response = new AssetOperationResponseModel();
		return $response->setSuccess();
	}

	/**
	 * Finalize an outgoing transfer for a file.
	 *
	 * @param AssetFileModel $file The assetFileModel representing the file that will have any created images deleted.
	 *
	 * @return null
	 */
	public function deleteCreatedImages(AssetFileModel $file)
	{
		$this->deleteGeneratedImageTransforms($file);
		$this->deleteGeneratedThumbnails($file);
	}

	/**
	 * Create a folder.
	 *
	 * @param AssetFolderModel $parentFolder The assetFolderModel representing the folder to create.
	 * @param string           $folderName   The name of the folder to create.
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function createFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$folderName = IOHelper::cleanFilename($folderName);

		// If folder exists in DB or physically, bail out
		if (craft()->assets->findFolder(array('parentId' => $parentFolder->id, 'name' => $folderName))
			|| $this->sourceFolderExists($parentFolder, $folderName))
		{
			throw new Exception(Craft::t('A folder already exists with that name!'));
		}

		if ( !$this->createSourceFolder($parentFolder, $folderName))
		{
			throw new Exception(Craft::t('There was an error while creating the folder.'));
		}

		$newFolder = new AssetFolderModel();
		$newFolder->sourceId = $parentFolder->sourceId;
		$newFolder->parentId = $parentFolder->id;
		$newFolder->name = $folderName;
		$newFolder->path = $parentFolder->path.$folderName.'/';

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
	 * @param AssetFolderModel $folder  The assetFolderModel representing the name of the folder to rename.
	 * @param string           $newName The new name of the folder.
	 *
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
		if (!(StringHelper::toLowerCase($newName) == StringHelper::toLowerCase($folder->name)) && $this->sourceFolderExists($parentFolder, $newName))
		{
			throw new Exception(Craft::t("Folder “{folder}” already exists there.", array('folder' => $newName)));
		}

		// Try to rename the folder in the source
		if (!$this->renameSourceFolder($folder, $newName))
		{
			throw new Exception(Craft::t("Cannot rename folder “{folder}”!", array('folder' => $folder->name)));
		}

		$oldFullPath = $folder->path;
		$newFullPath = IOHelper::getParentFolderPath($folder->path).$newName.'/';

		// Find all folders with affected fullPaths and update them.
		$folders = craft()->assets->getAllDescendantFolders($folder);
		foreach ($folders as $folderModel)
		{
			$folderModel->path = preg_replace('#^'.$oldFullPath.'#', $newFullPath, $folderModel->path);
			craft()->assets->storeFolder($folderModel);
		}

		// Now change the affected folder
		$folder->name = $newName;
		$folder->path = $newFullPath;
		craft()->assets->storeFolder($folder);

		// All set, Scotty!
		$response = new AssetOperationResponseModel();
		return $response->setSuccess()->setDataItem('newName', $newName);
	}

	/**
	 * Moves a folder.
	 *
	 * @param AssetFolderModel $folder          The assetFolderModel representing the existing folder.
	 * @param AssetFolderModel $newParentFolder The assetFolderModel representing the new parent folder.
	 * @param bool             $overwriteTarget If true, will overwrite folder, if needed
	 *
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
		if ($this->sourceFolderExists($newParentFolder, $folder->name))
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
					$this->deleteSourceFolder($newParentFolder, $folder->name);
				}
			}
			else
			{
				return $response->setPrompt($this->getUserFolderPromptOptions($folder->name, $folder->id))->setDataItem('folderId', $folder->id);
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
	 * Delete a folder.
	 *
	 * @param AssetFolderModel $folder The assetFolderModel representing the folder to be deleted.
	 *
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
		$this->deleteSourceFolder($parentFolder, $folder->name);

		craft()->assets->deleteFolderRecord($folder->id);

		$response = new AssetOperationResponseModel();
		return $response->setSuccess();
	}

	/**
	 * Returns whether this is a local source or not.
	 *
	 * @return bool
	 */
	public function isSourceLocal()
	{
		return $this->isSourceLocal;
	}

	/**
	 * Finalize a file transfer between sources for the provided file.
	 *
	 * @param AssetFileModel $file The assetFileModel representing the file we're finalizing the transfer for.
	 *
	 * @return mixed
	 */
	public function finalizeTransfer(AssetFileModel $file)
	{
		$this->deleteSourceFile($file->getFolder(), $file->filename);
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

	/**
	 * Gets a period list.
	 *
	 * @return array
	 */
	public function getPeriodList()
	{
		return array(
			PeriodType::Seconds => Craft::t('Seconds'),
			PeriodType::Minutes => Craft::t('Minutes'),
			PeriodType::Hours   => Craft::t('Hours'),
			PeriodType::Days    => Craft::t('Days'),
			PeriodType::Months  => Craft::t('Months'),
			PeriodType::Years   => Craft::t('Years'),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder   The assetFolderModel that the file will be inserted into.
	 * @param string           $filePath The filePath of the file to insert.
	 * @param string           $fileName The fileName of the file to insert.
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	abstract protected function insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName);

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder   The assetFolderModel that has the file to get a name replacement for.
	 * @param string           $fileName The name of the file to get a replacement name for.
	 *
	 * @return mixed
	 */
	abstract protected function getNameReplacement(AssetFolderModel $folder, $fileName);

	/**
	 * Delete just the file inside of a source for an Assets File.
	 *
	 * @param AssetFolderModel $folder   The assetFolderModel that contains the file to be deleted.
	 * @param string           $filename The name of the file to be deleted.
	 */
	abstract protected function deleteSourceFile(AssetFolderModel $folder, $filename);

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel   $file         The assetFileModel of the file to move.
	 * @param AssetFolderModel $targetFolder The assetFolderModel that is the target destination of the file.
	 * @param string           $fileName     The name of the file to move.
	 * @param bool             $overwrite    If true, will overwrite target destination, if necessary.
	 *
	 * @return AssetOperationResponseModel
	 */
	abstract protected function moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $overwrite = false);

	/**
	 * Delete generated image transforms for a File.
	 *
	 * @param AssetFileModel $file The assetFileModel that has the images to delete the transforms for
	 *
	 * @return mixed
	 */
	abstract protected function deleteGeneratedImageTransforms(AssetFileModel $file);

	/**
	 * Return true if a physical folder exists.
	 *
	 * @param AssetFolderModel $parentFolder The assetFolderModel that has the folder to check if it exists.
	 * @param string           $folderName   The name of the folder to check if it exists.
	 *
	 * @return bool
	 */
	abstract protected function sourceFolderExists(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Creates a physical folder, returns true on success.
	 *
	 * @param AssetFolderModel $parentFolder The assetFolderModel that has the parent folder of the folder to create.
	 * @param string           $folderName   The name of the folder to create.
	 *
	 * @return bool
	 */
	abstract protected function createSourceFolder(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $parentFolder The assetFolderModel that has the parent of the folder to be deleted
	 * @param string           $folderName   The name of the folder to be deleted.
	 *
	 * @return bool
	 */
	abstract protected function deleteSourceFolder(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Rename a source folder.
	 *
	 * @param AssetFolderModel $folder  The assetFolderModel that has the folder to be renamed.
	 * @param string           $newName The new name of the folder.
	 *
	 * @return bool
	 */
	abstract protected function renameSourceFolder(AssetFolderModel $folder, $newName);

	/**
	 * Determines if a file can be moved internally from original source.
	 *
	 * @param BaseAssetSourceType $originalSource The original source to check if a file can be moved from.
	 *
	 * @return mixed
	 */
	abstract protected function canMoveFileFrom(BaseAssetSourceType $originalSource);

	/**
	 * Return a result object for prompting the user about filename conflicts.
	 *
	 * @param string $fileName The file that is the cause of all the trouble.
	 *
	 * @return object
	 */
	protected function getUserPromptOptions($fileName)
	{
		return (object) array(
			'message' => Craft::t('File “{file}” already exists at target location.', array('file' => $fileName)),
			'choices' => array(
				array('value' => AssetsHelper::ActionKeepBoth, 'title' => Craft::t('Keep both')),
				array('value' => AssetsHelper::ActionReplace, 'title' => Craft::t('Replace it')),
				array('value' => AssetsHelper::ActionCancel, 'title' => Craft::t('Cancel'))
			)
		);
	}

	/**
	 * Return a result array for prompting the user about folder conflicts.
	 *
	 * @param string $folderName The file that caused of all trouble
	 * @param int    $folderId   The folder where the conflict took place.
	 *
	 * @return object
	 */
	protected function getUserFolderPromptOptions($folderName, $folderId)
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
	 * Ensure a folder entry exists in the DB for the full path and return it's id.
	 *
	 * @param string $fullPath The path to ensure the folder exists at.
	 *
	 * @return int
	 */
	protected function ensureFolderByFullPath($fullPath)
	{
		$parameters = new FolderCriteriaModel(array(
			'path' => $fullPath,
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
				$parameters->path = '';
				$parameters->parentId = ':empty:';
			}
			else
			{
				$parameters->path = join('/', $parts).'/';
			}

			// Look up the parent folder
			$parentFolder = craft()->assets->findFolder($parameters);
			if (is_null($parentFolder))
			{
				$parentId = ':empty:';
			}
			else
			{
				$parentId = $parentFolder->id;
			}

			$folderModel = new AssetFolderModel();
			$folderModel->sourceId = $this->model->id;
			$folderModel->parentId = $parentId;
			$folderModel->name = $folderName;
			$folderModel->path = $fullPath;

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
	 * @param array $folderList The full folder list to check if there are any missing from the source.
	 *
	 * @return array
	 */
	protected function getMissingFolders(array $folderList)
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
				$missingFolders[$folderModel->id] = $this->model->name.'/'.$folderModel->path;
			}
		}

		return $missingFolders;
	}

	/**
	 * Indexes a file.
	 *
	 * @param string $uriPath The URI path fo the file to index.
	 *
	 * @return AssetFileModel|bool|null
	 */
	protected function indexFile($uriPath)
	{
		$extension = IOHelper::getExtension($uriPath);

		if (IOHelper::isExtensionAllowed($extension))
		{
			$parts = explode('/', $uriPath);
			$fileName = array_pop($parts);

			$searchFullPath = join('/', $parts).(empty($parts) ? '' : '/');

			if (empty($searchFullPath))
			{
				$parentId = ':empty:';
			}
			else
			{
				$parentId = false;
			}

			$parentFolder = craft()->assets->findFolder(array(
				'sourceId' => $this->model->id,
				'path' => $searchFullPath,
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
	 * Delete all the generated images for this file.
	 *
	 * @param AssetFileModel $file The assetFileModel representing the file to delete any generated thumbnails for.
	 *
	 * @return null
	 */
	protected function deleteGeneratedThumbnails(AssetFileModel $file)
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
	 * Delete transform-related data for file.
	 *
	 * @param AssetFileModel $file The assetFileModel that represents the file to delete any transformed data for.
	 *
	 * @return null
	 */
	protected function deleteTransformData(AssetFileModel $file)
	{
		// Delete all the created images, such as transforms, thumbnails
		$this->deleteCreatedImages($file);
		craft()->assetTransforms->deleteTransformRecordsByFileId($file->id);

		$filePath = craft()->path->getAssetsImageSourcePath().$file->id.'.'.IOHelper::getExtension($file->filename);
		if (IOHelper::fileExists($filePath))
		{
			IOHelper::deleteFile($filePath);
		}
	}

	/**
	 * Purge a file from the Source's cache.  Sources that need this should override this method.
	 *
	 * @param AssetFolderModel $folder   The assetFolderModel representing the folder that has the file to purge.
	 * @param string           $filename The file to purge.
	 *
	 * @return null
	 */
	protected function purgeCachedSourceFile(AssetFolderModel $folder, $filename)
	{
		return;
	}

	/**
	 * Extract period amount and type from a saved Expires value.
	 *
	 * @param string $value The value to extract the expiry information from.
	 *
	 * @return array
	 */
	protected function extractExpiryInformation($value)
	{
		if (preg_match('/([0-9]+)([a-z]+)/i', $value, $matches))
		{
			return array('amount' => $matches[1], 'period' => $matches[2]);
		}
		else
		{
			return array('amount' => '', 'period' => '');
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Mirrors a subset of folder tree from one location to other.
	 *
	 * @param AssetFolderModel $newLocation  The assetFolderModel representing the new location for the folder mirror.
	 * @param AssetFolderModel $sourceFolder The assetFolderModel representing the source folder for the mirror operation.
	 * @param mixed            $changedData  Any data that changed during the mirroring operation.
	 *
	 * @throws Exception
	 * @return null
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
}
