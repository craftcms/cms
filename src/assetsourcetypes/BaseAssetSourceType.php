<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\assetsourcetypes;

use Craft;
use craft\app\components\BaseSavableComponentType;
use craft\app\enums\AssetConflictResolution;
use craft\app\enums\ElementType;
use craft\app\enums\PeriodType;
use craft\app\errors\Exception;
use craft\app\events\AssetEvent;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\ImageHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\elements\Asset;
use craft\app\models\AssetFolder as AssetFolderModel;
use craft\app\models\AssetOperationResponse as AssetOperationResponseModel;
use craft\app\models\AssetTransformIndex as AssetTransformIndexModel;
use craft\app\models\FolderCriteria as FolderCriteriaModel;
use craft\app\services\Assets;

/**
 * The base class for all asset source types.  Any asset source type must extend this class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	 * The type of component, e.g. "Plugin", "Widget", "FieldType", etc. Defined by the component type's base class.
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
	 * @param int    $offset    The offset of this index.
	 *
	 * @return mixed
	 */
	abstract public function processIndex($sessionId, $offset);

	/**
	 * Get the image source path.
	 *
	 * @param Asset $fileModel The Asset model for the image source path.
	 *
	 * @return mixed
	 */
	abstract public function getImageSourcePath(Asset $fileModel);

	/**
	 * Put an image transform for the File and Transform Index using the provided path to the source image.
	 *
	 * @param Asset                    $file        The Asset model that the transform belongs to
	 * @param AssetTransformIndexModel $index       The Transform Index data.
	 * @param string                   $sourceImage The source image.
	 *
	 * @return mixed
	 */
	abstract public function putImageTransform(Asset $file, AssetTransformIndexModel $index, $sourceImage);

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param Asset $file The Asset model that has the file to make a copy of.
	 *
	 * @return mixed
	 */
	abstract public function getLocalCopy(Asset $file);

	/**
	 * Return true if a physical folder exists.
	 *
	 * @param AssetFolderModel $parentFolder The AssetFolderModel model that has the folder to check if it exists.
	 * @param string           $folderName   The name of the folder to check if it exists.
	 *
	 * @return boolean
	 */
	abstract public function folderExists(AssetFolderModel $parentFolder, $folderName);

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
		return [];
	}

	/**
	 * Upload a file.
	 *
	 * @param AssetFolderModel $folder The AssetFolderModel model where the file should be uploaded to.
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function uploadFile(AssetFolderModel $folder)
	{
		// Upload the file and drop it in the temporary folder
		$file = $_FILES['assets-upload'];

		// Make sure a file was uploaded
		if (empty($file['name']))
		{
			throw new Exception(Craft::t('app', 'No file was uploaded'));
		}

		$size = $file['size'];

		// Make sure the file isn't empty
		if (!$size)
		{
			throw new Exception(Craft::t('app', 'Uploaded file was empty'));
		}

		$fileName = AssetsHelper::cleanAssetName($file['name']);

		// Save the file to a temp location and pass this on to the source type implementation
		$filePath = AssetsHelper::getTempFilePath(IOHelper::getExtension($fileName));
		move_uploaded_file($file['tmp_name'], $filePath);

		$response = $this->insertFileByPath($filePath, $folder, $fileName);

		// Make sure the file is removed.
		IOHelper::deleteFile($filePath, true);

		// Prevent sensitive information leak. Just in case.
		$response->deleteDataItem('filePath');

		return $response;
	}

	/**
	 * Insert a file into a folder by it's local path.
	 *
	 * @param string           $localFilePath    The local file path of the file to insert.
	 * @param AssetFolderModel $folder           The AssetFolderModel model where the file should be uploaded to.
	 * @param string           $fileName         The name of the file to insert.
	 * @param bool             $preventConflicts If set to true, will ensure that a conflict is not encountered by
	 *                                           checking the file name prior insertion.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function insertFileByPath($localFilePath, AssetFolderModel $folder, $fileName, $preventConflicts = false)
	{
		// Fire a 'beforeUploadAsset' event
		$event = new AssetEvent([
			'path'     => $localFilePath,
			'folder'   => $folder,
			'filename' => $fileName
		]);

		Craft::$app->assets->trigger(Assets::EVENT_BEFORE_UPLOAD_ASSET, $event);

		if ($event->performAction)
		{
			// We hate Javascript and PHP in our image files.
			if (IOHelper::getFileKind(IOHelper::getExtension($localFilePath)) == 'image' && ImageHelper::isImageManipulatable(IOHelper::getExtension($localFilePath)))
			{
				Craft::$app->images->cleanImage($localFilePath);
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

				$fileModel = new Asset();
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

				Craft::$app->assets->storeFile($fileModel);

				if (!$this->isSourceLocal() && $fileModel->kind == 'image')
				{
					Craft::$app->assetTransforms->storeLocalSource($localFilePath, Craft::$app->path->getAssetsImageSourcePath().'/'.$fileModel->id.'.'.IOHelper::getExtension($fileModel->filename));
				}

				// Check if we stored a conflict response originally - send that back then.
				if (isset($conflictResponse))
				{
					$response = $conflictResponse;
				}

				$response->setDataItem('fileId', $fileModel->id);
			}
		}
		else
		{
			$response = new AssetOperationResponseModel();
			$response->setError(Craft::t('app', 'The file upload was cancelled.'));
		}

		return $response;
	}

	/**
	 * Transfer a file into the source.
	 *
	 * @todo: Refactor this and moveFileInsideSource method - a lot of duplicate code.
	 *
	 * @param string           $localCopy          The local copy of the file to transfer.
	 * @param AssetFolderModel $folder        The AssetFolderModel model that contains the file to transfer.
	 * @param Asset            $file          The Asset model that represents the file to transfer.
	 * @param string           $conflictResolution The action to perform during the transfer.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function transferFileIntoSource($localCopy, AssetFolderModel $folder, Asset $file, $conflictResolution)
	{
		$filename = AssetsHelper::cleanAssetName($file->filename);

		if (!empty($conflictResolution))
		{
			switch ($conflictResolution)
			{
				case AssetConflictResolution::Replace:
				{
					$fileToReplace = Craft::$app->assets->findFile(['folderId' => $folder->id, 'filename' => $filename]);

					if ($fileToReplace)
					{
						$this->mergeFile($file, $fileToReplace);
					}
					else
					{
						$this->deleteSourceFile($folder->path.$filename);
					}

					break;
				}

				case AssetConflictResolution::KeepBoth:
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
			Craft::$app->assets->storeFile($file);

			if (!$this->isSourceLocal() && $file->kind == 'image')
			{
				// Store copy locally for all sorts of operations.
				Craft::$app->assetTransforms->storeLocalSource($localCopy, Craft::$app->path->getAssetsImageSourcePath().'/'.$file->id.'.'.IOHelper::getExtension($file->filename));
			}
		}

		return $response;
	}

	/**
	 * Move file from one path to another if it's possible. Return false on failure.
	 *
	 * @param BaseAssetSourceType $originalSource     The original source of the file being moved.
	 * @param Asset               $file               The Asset model representing the file to move.
	 * @param AssetFolderModel    $targetFolder       The AssetFolderModel model representing the target folder.
	 * @param string              $filename           The file name of the file to move.
	 * @param string              $conflictResolution The action to perform during the file move.
	 *
	 * @return bool|AssetOperationResponseModel
	 */
	public function moveFileInsideSource(BaseAssetSourceType $originalSource, Asset $file, AssetFolderModel $targetFolder, $filename, $conflictResolution = null)
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
		if (!empty($conflictResolution))
		{
			switch ($conflictResolution)
			{
				case AssetConflictResolution::Replace:
				{
					$fileToReplace = Craft::$app->assets->findFile(['folderId' => $targetFolder->id, 'filename' => $filename]);

					if ($fileToReplace)
					{
						$this->mergeFile($file, $fileToReplace);
						$this->purgeCachedSourceFile($targetFolder, $filename);
						$mergeFiles = true;
					}
					else
					{
						$this->deleteSourceFile($targetFolder->path.$filename);
						$this->purgeCachedSourceFile($targetFolder, $filename);
					}

					break;
				}

				case AssetConflictResolution::KeepBoth:
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
			Craft::$app->assets->storeFile($file);
		}

		return $response;
	}


	/**
	 * Replace physical file.
	 *
	 * @param Asset  $oldFile       The Asset model representing the original file.
	 * @param Asset  $replaceWith   The Asset model representing the new file.
	 * @param string $filenameToUse The filename to use for the replaced file. If left empty, will use the name of
	 *                              the new file.
	 *
	 * @return null
	 */
	public function replaceFile(Asset $oldFile, Asset $replaceWith, $filenameToUse = "")
	{
		if ($oldFile->kind == 'image')
		{
			Craft::$app->assetTransforms->deleteAllTransformData($oldFile);
			$this->deleteSourceFile($oldFile->getFolder()->path.$oldFile->filename);
			$this->purgeCachedSourceFile($oldFile->getFolder(), $oldFile->filename);

			// For remote sources, fetch the source image and move it in the old ones place
			if (!$this->isSourceLocal())
			{
				if ($replaceWith->kind == 'image')
				{
					$localCopy = $replaceWith->getTransformSource();
					IOHelper::copyFile($localCopy, Craft::$app->path->getAssetsImageSourcePath().'/'.$oldFile->id.'.'.IOHelper::getExtension($oldFile->filename));
				}
			}
		}

		$newFileName = !empty($filenameToUse) ? $filenameToUse : $oldFile->filename;
		$folder = Craft::$app->assets->getFolderById($oldFile->folderId);

		$filenameChanges = StringHelper::toLowerCase($newFileName) != StringHelper::toLowerCase($replaceWith->filename);

		// If the filename does not change, this can trigger errors in some source types.
		if ($filenameChanges)
		{
			$this->moveSourceFile($replaceWith, $folder, $newFileName, true);
		}

		// Update file info
		$oldFile->width        = $replaceWith->width;
		$oldFile->height       = $replaceWith->height;
		$oldFile->size         = $replaceWith->size;
		$oldFile->kind         = $replaceWith->kind;
		$oldFile->dateModified = $replaceWith->dateModified;
		$oldFile->filename     = $newFileName;

		if (empty($filenameToUse))
		{
			$replaceWith->filename = $this->getNameReplacement($folder, $replaceWith->filename);
			Craft::$app->assets->storeFile($replaceWith);
		}
		else
		{
			// If the file name has not changed, we're reusing the source file,
			// so we have to prevent deletion of source file here.
			Craft::$app->assets->deleteFiles($replaceWith->id, $filenameChanges);
		}

		Craft::$app->assets->storeFile($oldFile);
	}

	/**
	 * Delete a file.
	 *
	 * @param Asset $file The Asset model representing the file to delete.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function deleteFile(Asset $file)
	{
		Craft::$app->assetTransforms->deleteAllTransformData($file);

		// Delete DB record and the file itself.
		Craft::$app->elements->deleteElementById($file->id);

		$this->deleteSourceFile($file->getFolder()->path.$file->filename);

		$response = new AssetOperationResponseModel();

		return $response->setSuccess();
	}

	/**
	 * Merge a file.
	 *
	 * @param Asset $sourceFile The Asset model representing the file being merged.
	 * @param Asset $targetFile The Asset model representing the file that is being merged into.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function mergeFile(Asset $sourceFile, Asset $targetFile)
	{
		Craft::$app->assetTransforms->deleteAllTransformData($targetFile);

		// Delete DB record and the file itself.
		Craft::$app->elements->mergeElementsByIds($targetFile->id, $sourceFile->id);

		$this->deleteSourceFile($targetFile->getFolder()->path.$targetFile->filename);

		$response = new AssetOperationResponseModel();

		return $response->setSuccess();
	}

	/**
	 * Delete a generated transform for a file.
	 *
	 * @param Asset $file
	 * @param AssetTransformIndexModel $index
	 *
	 * @return null
	 */
	public function deleteTransform(Asset $file, AssetTransformIndexModel $index)
	{
		$folder = $file->getFolder();

		$this->deleteSourceFile($folder->path.Craft::$app->assetTransforms->getTransformSubpath($file, $index));
	}

	/**
	 * Copy a transform for a file from source location to target location.
	 *
	 * @param Asset                    $file         The Asset model that has the transform to copy.
	 * @param AssetFolderModel         $targetFolder The AssetFolderModel model that contains the target folder.
	 * @param AssetTransformIndexModel $source       The source transform index data.
	 * @param AssetTransformIndexModel $target       The destination transform index data.
	 *
	 * @return mixed
	 */
	public function copyTransform(Asset $file, AssetFolderModel $targetFolder, AssetTransformIndexModel $source, AssetTransformIndexModel $target)
	{
		$folder = $file->getFolder();

		$sourceTransformPath = $folder->path.Craft::$app->assetTransforms->getTransformSubpath($file, $source);
		$targetTransformPath = $targetFolder->path.Craft::$app->assetTransforms->getTransformSubpath($file, $target);

		return $this->copySourceFile($sourceTransformPath, $targetTransformPath);
	}


	/**
	 * Create a folder.
	 *
	 * @param AssetFolderModel $parentFolder The AssetFolderModel model representing the folder to create.
	 * @param string           $folderName   The name of the folder to create.
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function createFolder(AssetFolderModel $parentFolder, $folderName)
	{
		$folderName = AssetsHelper::cleanAssetName($folderName, false);

		// If folder exists in DB or physically, bail out
		if (Craft::$app->assets->findFolder(['parentId' => $parentFolder->id, 'name' => $folderName])
			|| $this->folderExists($parentFolder, $folderName))
		{
			throw new Exception(Craft::t('app', 'A folder already exists with that name!'));
		}

		if ( !$this->createSourceFolder($parentFolder, $folderName))
		{
			throw new Exception(Craft::t('app', 'There was an error while creating the folder.'));
		}

		$newFolder = new AssetFolderModel();
		$newFolder->sourceId = $parentFolder->sourceId;
		$newFolder->parentId = $parentFolder->id;
		$newFolder->name = $folderName;
		$newFolder->path = $parentFolder->path.$folderName.'/';

		$folderId = Craft::$app->assets->storeFolder($newFolder);

		$response = new AssetOperationResponseModel();

		return $response->setSuccess()
			->setDataItem('folderId', $folderId)
			->setDataItem('parentId', $parentFolder->id)
			->setDataItem('folderName', $folderName);
	}

	/**
	 * Rename a folder.
	 *
	 * @param AssetFolderModel $folder  The AssetFolderModel model representing the name of the folder to rename.
	 * @param string           $newName The new name of the folder.
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function renameFolder(AssetFolderModel $folder, $newName)
	{
		$parentFolder = Craft::$app->assets->getFolderById($folder->parentId);

		if (!$parentFolder)
		{
			throw new Exception(Craft::t('app', 'Cannot rename folder “{folder}”!', ['folder' => $folder->name]));
		}

		// Allow this for changing the case
		if (!(StringHelper::toLowerCase($newName) == StringHelper::toLowerCase($folder->name)) && $this->folderExists($parentFolder, $newName))
		{
			throw new Exception(Craft::t('app', 'Folder “{folder}” already exists there.', ['folder' => $newName]));
		}

		// Try to rename the folder in the source
		if (!$this->renameSourceFolder($folder, $newName))
		{
			throw new Exception(Craft::t('app', 'Cannot rename folder “{folder}”!', ['folder' => $folder->name]));
		}

		$oldFullPath = $folder->path;
		$newFullPath = IOHelper::getParentFolderPath($folder->path).$newName.'/';

		// Find all folders with affected fullPaths and update them.
		$folders = Craft::$app->assets->getAllDescendantFolders($folder);

		foreach ($folders as $folderModel)
		{
			$folderModel->path = preg_replace('#^'.$oldFullPath.'#', $newFullPath, $folderModel->path);
			Craft::$app->assets->storeFolder($folderModel);
		}

		// Now change the affected folder
		$folder->name = $newName;
		$folder->path = $newFullPath;
		Craft::$app->assets->storeFolder($folder);

		// All set, Scotty!
		$response = new AssetOperationResponseModel();

		return $response->setSuccess()->setDataItem('newName', $newName);
	}

	/**
	 * Moves a folder.
	 *
	 * @param AssetFolderModel $folder          The AssetFolderModel model representing the existing folder.
	 * @param AssetFolderModel $newParentFolder The AssetFolderModel model representing the new parent folder.
	 * @param bool             $overwriteTarget If true, will overwrite folder, if needed.
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

		if ($this->folderExists($newParentFolder, $folder->name))
		{
			if ($overwriteTarget)
			{
				$existingFolder = Craft::$app->assets->findFolder(['parentId' => $newParentFolder->id, 'name' => $folder->name]);

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

		$response->setSuccess()->setDataItem('deleteList', [$folder->id])->setDataItem('removeFromTree', $removeFromTree);

		$mirroringData = ['changedFolderIds' => []];
		$this->_mirrorStructure($newParentFolder, $folder, $mirroringData);

		$response->setDataItem('changedFolderIds', $mirroringData['changedFolderIds']);

		$criteria = Craft::$app->elements->getCriteria(ElementType::Asset);
		$criteria->folderId = array_keys(Craft::$app->assets->getAllDescendantFolders($folder));
		$files = $criteria->find();

		$transferList = [];

		foreach ($files as $file)
		{
			$transferList[] = [
				'fileId' => $file->id,
				'folderId' => $mirroringData['changedFolderIds'][$file->folderId]['newId'],
				'fileName' => $file->filename
			];
		}

		return $response->setDataItem('transferList', $transferList);
	}

	/**
	 * Delete a folder.
	 *
	 * @param AssetFolderModel $folder The AssetFolderModel model representing the folder to be deleted.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function deleteFolder(AssetFolderModel $folder)
	{
		// Get rid of children files
		$criteria = Craft::$app->elements->getCriteria(ElementType::Asset);
		$criteria->folderId = $folder->id;
		$files = $criteria->find();

		foreach ($files as $file)
		{
			$this->deleteFile($file);
		}

		// Delete children folders
		$childFolders = Craft::$app->assets->findFolders(['parentId' => $folder->id]);

		foreach ($childFolders as $childFolder)
		{
			$this->deleteFolder($childFolder);
		}

		$parentFolder = Craft::$app->assets->getFolderById($folder->parentId);
		$this->deleteSourceFolder($parentFolder, $folder->name);

		Craft::$app->assets->deleteFolderRecord($folder->id);

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
	 * @param Asset $file The Asset model representing the file we're finalizing the transfer for.
	 *
	 * @return null
	 */
	public function finalizeTransfer(Asset $file)
	{
		$this->deleteSourceFile($file->getFolder()->path.$file->filename);
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
		return [
			PeriodType::Seconds => Craft::t('app', 'Seconds'),
			PeriodType::Minutes => Craft::t('app', 'Minutes'),
			PeriodType::Hours   => Craft::t('app', 'Hours'),
			PeriodType::Days    => Craft::t('app', 'Days'),
			PeriodType::Months  => Craft::t('app', 'Months'),
			PeriodType::Years   => Craft::t('app', 'Years'),
		];
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder   The AssetFolderModel model that the file will be inserted into.
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
	 * @param AssetFolderModel $folder   The AssetFolderModel model that has the file to get a name replacement for.
	 * @param string           $fileName The name of the file to get a replacement name for.
	 *
	 * @return mixed
	 */
	abstract protected function getNameReplacement(AssetFolderModel $folder, $fileName);

	/**
	 * Delete just the file inside of a source for an Assets File.
	 *
	 * @param string $subpath The subpath of the file to delete within the source
	 */
	abstract protected function deleteSourceFile($subpath);

	/**
	 * Move a file in source.
	 *
	 * @param Asset            $file         The Asset model of the file to move.
	 * @param AssetFolderModel $targetFolder The AssetFolderModel model that is the target destination of the file.
	 * @param string           $fileName     The name of the file to move.
	 * @param bool             $overwrite    If true, will overwrite target destination, if necessary.
	 *
	 * @return AssetOperationResponseModel
	 */
	abstract protected function moveSourceFile(Asset $file, AssetFolderModel $targetFolder, $fileName = '', $overwrite = false);

	/**
	 * Copy a physical file inside the source.
	 *
	 * @param string $sourceUri source subpath
	 * @param string $targetUri target subpath
	 *
	 * @return bool
	 */
	abstract protected function copySourceFile($sourceUri, $targetUri);

	/**
	 * Creates a physical folder, returns true on success.
	 *
	 * @param AssetFolderModel $parentFolder The AssetFolderModel model that has the parent folder of the folder to create.
	 * @param string           $folderName   The name of the folder to create.
	 *
	 * @return bool
	 */
	abstract protected function createSourceFolder(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $parentFolder The AssetFolderModel model that has the parent of the folder to be deleted
	 * @param string           $folderName   The name of the folder to be deleted.
	 *
	 * @return bool
	 */
	abstract protected function deleteSourceFolder(AssetFolderModel $parentFolder, $folderName);

	/**
	 * Rename a source folder.
	 *
	 * @param AssetFolderModel $folder  The AssetFolderModel model that has the folder to be renamed.
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
		return (object) [
			'message' => Craft::t('app', 'File “{file}” already exists at target location.', ['file' => $fileName]),
			'choices' => [
				['value' => AssetConflictResolution::KeepBoth, 'title' => Craft::t('app', 'Keep both')],
				['value' => AssetConflictResolution::Replace, 'title' => Craft::t('app', 'Replace it')],
				['value' => AssetConflictResolution::Cancel, 'title' => Craft::t('app', 'Cancel')]
			]
		];
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
		return [
			'message' => Craft::t('app', 'Folder “{folder}” already exists at target location', ['folder' => $folderName]),
			'file_name' => $folderId,
			'choices' => [
				['value' => AssetConflictResolution::Replace, 'title' => Craft::t('app', 'Replace the existing folder')],
				['value' => AssetConflictResolution::Cancel, 'title' => Craft::t('app', 'Cancel the folder move.')]
			]
		];
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
		$parameters = new FolderCriteriaModel([
			'path' => $fullPath,
			'sourceId' => $this->model->id
		]);

		$folderModel = Craft::$app->assets->findFolder($parameters);

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
			$parentFolder = Craft::$app->assets->findFolder($parameters);

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

			return Craft::$app->assets->storeFolder($folderModel);
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
		$missingFolders = [];

		$allFolders = Craft::$app->assets->findFolders([
			'sourceId' => $this->model->id
		]);

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
	 * @return Asset|bool|null
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

			$parentFolder = Craft::$app->assets->findFolder([
				'sourceId' => $this->model->id,
				'path' => $searchFullPath,
				'parentId' => $parentId
			]);

			if (empty($parentFolder))
			{
				return false;
			}

			$folderId = $parentFolder->id;

			$fileModel = Craft::$app->assets->findFile([
				'folderId' => $folderId,
				'filename' => $fileName
			]);

			if (is_null($fileModel))
			{
				$fileModel = new Asset();
				$fileModel->sourceId = $this->model->id;
				$fileModel->folderId = $folderId;
				$fileModel->filename = $fileName;
				$fileModel->kind = IOHelper::getFileKind($extension);
				Craft::$app->assets->storeFile($fileModel);
			}

			return $fileModel;
		}

		return false;
	}

	/**
	 * Delete all the generated images for this file.
	 *
	 * @param Asset $file The Asset model representing the file to delete any generated thumbnails for.
	 *
	 * @return null
	 */
	protected function deleteGeneratedThumbnails(Asset $file)
	{
		$thumbFolders = IOHelper::getFolderContents(Craft::$app->path->getAssetsThumbsPath());

		foreach ($thumbFolders as $folder)
		{
			if (is_dir($folder))
			{
				IOHelper::deleteFile($folder.'/'.$file->id.'.'.IOHelper::getExtension($file->filename));
			}
		}
	}

	/**
	 * Purge a file from the Source's cache.  Sources that need this should override this method.
	 *
	 * @param AssetFolderModel $folder   The AssetFolderModel model representing the folder that has the file to purge.
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
			return ['amount' => $matches[1], 'period' => $matches[2]];
		}
		else
		{
			return ['amount' => '', 'period' => ''];
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Mirrors a subset of folder tree from one location to other.
	 *
	 * @param AssetFolderModel $newLocation  The AssetFolderModel model representing the new location for the folder mirror.
	 * @param AssetFolderModel $sourceFolder The AssetFolderModel model representing the source folder for the mirror
	 *                                       operation.
	 * @param mixed       $changedData       Any data that changed during the mirroring operation.
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

			$changedData['changedFolderIds'][$sourceFolder->id] = [
				'newId' => $newId,
				'newParentId' => $parentId
			];

			$newTargetRow = Craft::$app->assets->getFolderById($newId);

			$children = Craft::$app->assets->findFolders(['parentId' => $sourceFolder->id]);

			foreach ($children as $child)
			{
				$this->_mirrorStructure($newTargetRow, $child, $changedData);
			}
		}
		else
		{
			throw new Exception(Craft::t('app', 'Failed to successfully mirror folder structure'));
		}
	}
}
