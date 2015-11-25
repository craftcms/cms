<?php
namespace Craft;

/**
 * Class AssetsService
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.services
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
 */
class AssetsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_foldersById;

	/**
	 * A flag that designates that a file merge is in progress and name uniqueness
	 * should not be enforced.
	 *
	 * @var bool
	 */
	private $_mergeInProgress = false;

	// Public Methods
	// =========================================================================

	/**
	 * Returns all top-level files in a source.
	 *
	 * @param int         $sourceId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getFilesBySourceId($sourceId, $indexBy = null)
	{
		$files = craft()->db->createCommand()
			->select('fi.*')
			->from('assetfiles fi')
			->join('assetfolders fo', 'fo.id = fi.folderId')
			->where('fo.sourceId = :sourceId', array(':sourceId' => $sourceId))
			->order('fi.filename')
			->queryAll();

		return AssetFileModel::populateModels($files, $indexBy);
	}

	/**
	 * Returns a file by its ID.
	 *
	 * @param             $fileId
	 * @param string|null $localeId
	 *
	 * @return AssetFileModel|null
	 */
	public function getFileById($fileId, $localeId = null)
	{
		return craft()->elements->getElementById($fileId, ElementType::Asset, $localeId);
	}

	/**
	 * Finds the first file that matches the given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return AssetFileModel|null
	 */
	public function findFile($criteria = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria(ElementType::Asset, $criteria);
		}

		if (isset($criteria->filename))
		{
			$criteria->filename = StringHelper::escapeCommas($criteria->filename);
		}

		return $criteria->first();
	}

	/**
	 * Gets the total number of files that match a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return int
	 */
	public function getTotalFiles($criteria = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria(ElementType::Asset, $criteria);
		}

		return $criteria->total();
	}

	/**
	 * Saves the record for an asset.
	 *
	 * @param AssetFileModel $file
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function storeFile(AssetFileModel $file)
	{
		$isNewFile = !$file->id;

		if (!$isNewFile)
		{
			$fileRecord = AssetFileRecord::model()->findById($file->id);

			if (!$fileRecord)
			{
				throw new Exception(Craft::t("No asset exists with the ID “{id}”.", array('id' => $file->id)));
			}
		}
		else
		{
			$fileRecord = new AssetFileRecord();
		}

		$fileRecord->sourceId     = $file->sourceId;
		$fileRecord->folderId     = $file->folderId;
		$fileRecord->filename     = $file->filename;
		$fileRecord->kind         = $file->kind;
		$fileRecord->size         = $file->size;
		$fileRecord->width        = $file->width;
		$fileRecord->height       = $file->height;
		$fileRecord->dateModified = $file->dateModified;

		$fileRecord->validate();
		$file->addErrors($fileRecord->getErrors());

		if ($file->hasErrors())
		{
			return false;
		}

		if ($isNewFile && !$file->getContent()->title)
		{
			// Give it a default title based on the file name
			$file->getContent()->title = $file->generateAttributeLabel(IOHelper::getFileName($file->filename, false));
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeSaveAsset' event
			$event = new Event($this, array(
				'asset'      => $file,
				'isNewAsset' => $isNewFile
			));

			$this->onBeforeSaveAsset($event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element
				$success = craft()->elements->saveElement($file, false);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveAsset
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					return false;
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewFile)
				{
					$fileRecord->id = $file->id;
				}

				// Save the file row
				$fileRecord->save(false);
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the asset, in case something changed
			// in onBeforeSaveAsset
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			// Fire an 'onSaveAsset' event
			$this->onSaveAsset(new Event($this, array(
				'asset'      => $file,
				'isNewAsset' => $isNewFile
			)));

			if ($this->hasEventHandler('onSaveFileContent'))
			{
				// Fire an 'onSaveFileContent' event (deprecated)
				$this->onSaveFileContent(new Event($this, array(
					'file' => $file
				)));
			}
		}

		return $success;
	}

	//  Folders
	// -------------------------------------------

	/**
	 * Store a folder by model and return the id.
	 *
	 * @param AssetFolderModel $folder
	 *
	 * @return mixed
	 */
	public function storeFolder(AssetFolderModel $folder)
	{
		if (empty($folder->id))
		{
			$record = new AssetFolderRecord();
		}
		else
		{
			$record = AssetFolderRecord::model()->findById($folder->id);
		}

		$record->parentId = $folder->parentId;
		$record->sourceId = $folder->sourceId;
		$record->name = $folder->name;
		$record->path = $folder->path;
		$record->save();

		return $record->id;
	}

	/**
	 * Get the folder tree for Assets by source ids
	 *
	 * @param $allowedSourceIds
	 *
	 * @return array
	 */
	public function getFolderTreeBySourceIds($allowedSourceIds)
	{
		if (empty($allowedSourceIds))
		{
			return array();
		}

		$folders = $this->findFolders(array('sourceId' => $allowedSourceIds, 'order' => 'path'));
		$tree = $this->_getFolderTreeByFolders($folders);

		$sort = array();

		foreach ($tree as $topFolder)
		{
			$sort[] = craft()->assetSources->getSourceById($topFolder->sourceId)->sortOrder;
		}

		array_multisort($sort, $tree);

		return $tree;
	}

	/**
	 * Get the users Folder model.
	 *
	 * @param UserModel $userModel
	 *
	 * @throws Exception
	 * @return AssetFolderModel|null
	 */
	public function getUserFolder(UserModel $userModel = null)
	{
		$sourceTopFolder = craft()->assets->findFolder(array('sourceId' => ':empty:', 'parentId' => ':empty:'));

		// Super unlikely, but would be very awkward if this happened without any contingency plans in place.
		if (!$sourceTopFolder)
		{
			$sourceTopFolder = new AssetFolderModel();
			$sourceTopFolder->name = TempAssetSourceType::sourceName;
			$sourceTopFolder->id = $this->storeFolder($sourceTopFolder);
		}

		if ($userModel)
		{
			$folderName = 'user_'.$userModel->id;
		}
		else
		{
			// A little obfuscation never hurt anyone
			$folderName = 'user_'.sha1(craft()->httpSession->getSessionID());
		}

		$folderCriteria = new FolderCriteriaModel(array(
			'name' => $folderName,
			'parentId' => $sourceTopFolder->id
		));

		$folder = $this->findFolder($folderCriteria);

		if (!$folder)
		{
			$folder = new AssetFolderModel();
			$folder->parentId = $sourceTopFolder->id;
			$folder->name = $folderName;
			$folder->id = $this->storeFolder($folder);
		}

		return $folder;
	}

	/**
	 * Get the folder tree for Assets by a folder id.
	 *
	 * @param $folderId
	 *
	 * @return array
	 */
	public function getFolderTreeByFolderId($folderId)
	{
		$folder = $this->getFolderById($folderId);

		if (is_null($folder))
		{
			return array();
		}

		return $this->_getFolderTreeByFolders(array($folder));
	}

	/**
	 * Create a folder by it's parent id and a folder name.
	 *
	 * @param $parentId
	 * @param $folderName
	 *
	 * @return AssetOperationResponseModel
	 */
	public function createFolder($parentId, $folderName)
	{
		try
		{
			$parentFolder = $this->getFolderById($parentId);

			if (empty($parentFolder))
			{
				throw new Exception(Craft::t("Can’t find the parent folder!"));
			}

			$source = craft()->assetSources->getSourceTypeById($parentFolder->sourceId);
			$response = $source->createFolder($parentFolder, $folderName);
		}
		catch (Exception $exception)
		{
			$response = new AssetOperationResponseModel();
			$response->setError($exception->getMessage());
		}

		return $response;
	}

	/**
	 * Rename a folder by it's folder and a new name.
	 *
	 * @param $folderId
	 * @param $newName
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function renameFolder($folderId, $newName)
	{
		try
		{
			$folder = $this->getFolderById($folderId);

			if (empty($folder))
			{
				throw new Exception(Craft::t("Can’t find the folder to rename!"));
			}

			$source = craft()->assetSources->getSourceTypeById($folder->sourceId);
			$response = $source->renameFolder($folder, AssetsHelper::cleanAssetName($newName, false));
		}
		catch (Exception $exception)
		{
			$response = new AssetOperationResponseModel();
			$response->setError($exception->getMessage());
		}

		return $response;
	}

	/**
	 * Move a folder.
	 *
	 * @param $folderId
	 * @param $newParentId
	 * @param $action
	 *
	 * @return AssetOperationResponseModel
	 */
	public function moveFolder($folderId, $newParentId, $action)
	{
		$folder = $this->getFolderById($folderId);
		$newParentFolder = $this->getFolderById($newParentId);

		try
		{
			if (!($folder && $newParentFolder))
			{
				$response = new AssetOperationResponseModel();
				$response->setError(Craft::t("Error moving folder - either source or target folders cannot be found"));
			}
			else
			{
				$newSourceType = craft()->assetSources->getSourceTypeById($newParentFolder->sourceId);
				$response = $newSourceType->moveFolder($folder, $newParentFolder, !empty($action));
			}
		}
		catch (Exception $exception)
		{
			$response = new AssetOperationResponseModel();
			$response->setError($exception->getMessage());
		}

		return $response;
	}

	/**
	 * Deletes a folder by its ID.
	 *
	 * @param int $folderId
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	public function deleteFolderById($folderId)
	{
		try
		{
			$folder = $this->getFolderById($folderId);

			if (empty($folder))
			{
				throw new Exception(Craft::t("Can’t find the folder!"));
			}

			$source = craft()->assetSources->getSourceTypeById($folder->sourceId);
			$response = $source->deleteFolder($folder);

		}
		catch (Exception $exception)
		{
			$response = new AssetOperationResponseModel();
			$response->setError($exception->getMessage());
		}

		return $response;
	}

	/**
	 * Returns a folder by its ID.
	 *
	 * @param int $folderId
	 *
	 * @return AssetFolderModel|null
	 */
	public function getFolderById($folderId)
	{
		if (!isset($this->_foldersById) || !array_key_exists($folderId, $this->_foldersById))
		{
			$result = $this->_createFolderQuery()
				->where('id = :id', array(':id' => $folderId))
				->queryRow();

			if ($result)
			{
				$folder = new AssetFolderModel($result);
			}
			else
			{
				$folder = null;
			}

			$this->_foldersById[$folderId] = $folder;
		}

		return $this->_foldersById[$folderId];
	}

	/**
	 * Finds folders that match a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return array
	 */
	public function findFolders($criteria = null)
	{
		if (!($criteria instanceof FolderCriteriaModel))
		{
			$criteria = new FolderCriteriaModel($criteria);
		}

		$query = craft()->db->createCommand()
			->select('f.*')
			->from('assetfolders AS f');

		$this->_applyFolderConditions($query, $criteria);

		if ($criteria->order)
		{
			$query->order($criteria->order);
		}

		if ($criteria->offset)
		{
			$query->offset($criteria->offset);
		}

		if ($criteria->limit)
		{
			$query->limit($criteria->limit);
		}

		$results = $query->queryAll();
		$folders = array();

		foreach ($results as $result)
		{
			$folder = AssetFolderModel::populateModel($result);
			$this->_foldersById[$folder->id] = $folder;
			$folders[] = $folder;
		}

		return $folders;
	}

	/**
	 * Returns all of the folders that are descendants of a given folder.
	 *
	 * @param AssetFolderModel $parentFolder
	 *
	 * @return array
	 */
	public function getAllDescendantFolders(AssetFolderModel $parentFolder)
	{
		$query = craft()->db->createCommand()
			->select('f.*')
			->from('assetfolders AS f')
			->where(array('like', 'path', $parentFolder->path.'%'))
			->andWhere('sourceId = :sourceId', array(':sourceId' => $parentFolder->sourceId));

		$results = $query->queryAll();
		$descendantFolders = array();

		foreach ($results as $result)
		{
			$folder = AssetFolderModel::populateModel($result);
			$this->_foldersById[$folder->id] = $folder;
			$descendantFolders[$folder->id] = $folder;
		}

		return $descendantFolders;
	}

	/**
	 * Finds the first folder that matches a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return AssetFolderModel|null
	 */
	public function findFolder($criteria = null)
	{
		if (!($criteria instanceof FolderCriteriaModel))
		{
			$criteria = new FolderCriteriaModel($criteria);
		}

		$criteria->limit = 1;
		$folder = $this->findFolders($criteria);

		if (is_array($folder) && !empty($folder))
		{
			return array_pop($folder);
		}

		return null;
	}

	/**
	 * Returns the root folder for a given source ID.
	 *
	 * @param int $sourceId
	 *
	 * @return AssetFolderModel|null
	 */
	public function getRootFolderBySourceId($sourceId)
	{
		return $this->findFolder(array(
			'sourceId' => $sourceId,
			'parentId' => ':empty:'
		));
	}

	/**
	 * Gets the total number of folders that match a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return int
	 */
	public function getTotalFolders($criteria)
	{
		if (!($criteria instanceof FolderCriteriaModel))
		{
			$criteria = new FolderCriteriaModel($criteria);
		}

		$query = craft()->db->createCommand()
			->select('count(id)')
			->from('assetfolders AS f');

		$this->_applyFolderConditions($query, $criteria);

		return (int)$query->queryScalar();
	}

	// File and folder managing
	// -------------------------------------------------------------------------

	/**
	 * @param int    $folderId     The Id of the folder the file is being uploaded to.
	 * @param string $userResponse User response regarding filename conflict.
	 * @param int    $theNewFileId The new file ID that has triggered the conflict.
	 * @param string $fileName     The filename that is in the conflict.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function uploadFile($folderId, $userResponse = '', $theNewFileId = 0, $fileName = '')
	{
		try
		{
			// handle a user's conflict resolution response
			if (!empty($userResponse))
			{
				return $this->_resolveUploadConflict($userResponse, $theNewFileId, $fileName);
			}

			$folder = $this->getFolderById($folderId);
			$source = craft()->assetSources->getSourceTypeById($folder->sourceId);

			return $source->uploadFile($folder);
		}
		catch (Exception $exception)
		{
			$response = new AssetOperationResponseModel();
			$response->setError(Craft::t('Error uploading the file: {error}', array('error' => $exception->getMessage())));

			return $response;
		}
	}

	/**
	 * Saves a file into an asset folder.
	 *
	 * This can be used to store newly-uploaded files:
	 *
	 * ```php
	 * $uploadedFile = UploadedFile::getInstanceByName('photo');
	 * $folderId = 10;
	 *
	 * $response = craft()->assets->insertFileByLocalPath(
	 *     $uploadedFile->tempName,
	 *     $uploadedFile->name,
	 *     $folderId,
	 *     AssetConflictResolution::KeepBoth
	 * );
	 *
	 * if ($response->isSuccess())
	 * {
	 *     $fileId = $response->getDataItem('fileId');
	 *     // ...
	 * }
	 * ```
	 *
	 * @param string $localPath          The local path to the file.
	 * @param string $fileName           The name that the file should be given when saved in the asset folder.
	 * @param int    $folderId           The ID of the folder that the file should be saved into.
	 * @param string $conflictResolution What action should be taken in the event of a filename conflict, if any
	 *                                   (`AssetConflictResolution::KeepBoth`, `AssetConflictResolution::Replace`,
	 *                                   or `AssetConflictResolution::Cancel).
	 *
	 * @return AssetOperationResponseModel
	 */
	public function insertFileByLocalPath($localPath, $fileName, $folderId, $conflictResolution = null)
	{
		$folder = $this->getFolderById($folderId);

		if (!$folder)
		{
			return false;
		}

		$fileName = AssetsHelper::cleanAssetName($fileName);
		$source = craft()->assetSources->getSourceTypeById($folder->sourceId);

		$response = $source->insertFileByPath($localPath, $folder, $fileName);

		if ($response->isConflict() && $conflictResolution)
		{
			$theNewFileId = $response->getDataItem('fileId');
			$response = $this->_resolveUploadConflict($conflictResolution, $theNewFileId, $fileName);
		}

		return $response;
	}

	/**
	 * Returns true, if a file is in the process os being merged.
	 *
	 * @return bool
	 */
	public function isMergeInProgress()
	{
		return $this->_mergeInProgress;
	}

	/**
	 * Delete a list of files by an array of ids (or a single id).
	 *
	 * @param array $fileIds
	 * @param bool $deleteFile Should the file be deleted along the record. Defaults to true.
	 *
	 * @return AssetOperationResponseModel
	 */
	public function deleteFiles($fileIds, $deleteFile = true)
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		$response = new AssetOperationResponseModel();

		try
		{
			foreach ($fileIds as $fileId)
			{
				$file = $this->getFileById($fileId);
				$source = craft()->assetSources->getSourceTypeById($file->sourceId);

				// Fire an 'onBeforeDeleteAsset' event
				$event = new Event($this, array(
					'asset' => $file
				));

				$this->onBeforeDeleteAsset($event);

				if ($event->performAction)
				{
					if ($deleteFile)
					{
						$source->deleteFile($file);
					}

					craft()->elements->deleteElementById($fileId);

					// Fire an 'onDeleteAsset' event
					$this->onDeleteAsset(new Event($this, array(
						'asset' => $file
					)));
				}
			}

			$response->setSuccess();
		}
		catch (Exception $exception)
		{
			$response->setError($exception->getMessage());
		}

		return $response;
	}

	/**
	 * Move or rename files.
	 *
	 * @param        $fileIds
	 * @param        $folderId
	 * @param string $filename If this is a rename operation or not.
	 * @param array  $actions  Actions to take in case of a conflict.
	 *
	 * @throws Exception
	 * @return bool|AssetOperationResponseModel
	 */
	public function moveFiles($fileIds, $folderId, $filename = '', $actions = array())
	{
		if ($filename && is_array($fileIds) && count($fileIds) > 1)
		{
			throw new Exception(Craft::t("It’s not possible to rename multiple files!"));
		}

		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		if (!is_array($actions))
		{
			$actions = array($actions);
		}

		$results = array();

		$response = new AssetOperationResponseModel();

		// Make sure the filename is allowed
		if ($filename)
		{
			$extension = IOHelper::getExtension($filename);

			if (!IOHelper::isExtensionAllowed($extension))
			{
				$response->setError(Craft::t('This file type is not allowed'));
				return $response;
			}
		}

		$folder = $this->getFolderById($folderId);
		$newSourceType = craft()->assetSources->getSourceTypeById($folder->sourceId);

		// Does the source folder exist?
		$parent = $folder->getParent();

		if ($parent && $folder->parentId && !$newSourceType->folderExists(($parent ? $parent->path : ''), $folder->name))
		{
			$response->setError(Craft::t("The target folder does not exist!"));
		}
		else
		{
			foreach ($fileIds as $i => $fileId)
			{
				$file = $this->getFileById($fileId);

				// If this is not a rename operation, then the filename remains the original
				if (count($fileIds) > 1 || empty($filename))
				{
					$filename = $file->filename;
				}

				// If the new file does not have an extension, give it the old file extension.
				if (!IOHelper::getExtension($filename))
				{
					$filename .= '.'.$file->getExtension();
				}

				$filename = AssetsHelper::cleanAssetName($filename);

				if ($folderId == $file->folderId && ($filename == $file->filename))
				{
					$response = new AssetOperationResponseModel();
					$response->setSuccess();
					$results[] = $response;
				}

				$originalSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);

				if ($originalSourceType && $newSourceType)
				{
					if (!$response = $newSourceType->moveFileInsideSource($originalSourceType, $file, $folder, $filename, $actions[$i]))
					{
						$response = $this->_moveFileBetweenSources($originalSourceType, $newSourceType, $file, $folder, $actions[$i]);
					}
				}
				else
				{
					$response->setError(Craft::t("There was an error moving the file {file}.", array('file' => $file->filename)));
				}
			}
		}

		return $response;
	}


	/**
	 * @param AssetFileModel $file
	 * @param string         $filename
	 * @param string         $action The action to take in case of a conflict.
	 *
	 * @return bool|AssetOperationResponseModel
	 */
	public function renameFile(AssetFileModel $file, $filename, $action = '')
	{
		$response = $this->moveFiles(array($file->id), $file->folderId, $filename, $action);

		// Set the new filename, if rename was successful
		if ($response->isSuccess())
		{
			$file->filename = $response->getDataItem('newFileName');
		}

		return $response;
	}

	/**
	 * Delete a folder record by id.
	 *
	 * @param $folderId
	 *
	 * @return bool
	 */
	public function deleteFolderRecord($folderId)
	{
		return (bool) AssetFolderRecord::model()->deleteAll('id = :folderId', array(':folderId' => $folderId));
	}

	/**
	 * Get URL for a file.
	 *
	 * @param AssetFileModel $file
	 * @param string         $transform
	 *
	 * @return string
	 */
	public function getUrlForFile(AssetFileModel $file, $transform = null)
	{
		if (!$transform || !ImageHelper::isImageManipulatable(IOHelper::getExtension($file->filename)))
		{
			$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
			return AssetsHelper::generateUrl($sourceType, $file);
		}

		// Get the transform index model
		$index = craft()->assetTransforms->getTransformIndex($file, $transform);

		// Does the file actually exist?
		if ($index->fileExists)
		{
			return craft()->assetTransforms->getUrlForTransformByTransformIndex($index);
		}
		else
		{
			if (craft()->config->get('generateTransformsBeforePageLoad'))
			{
				// Mark the transform as in progress
				$index->inProgress = true;
				craft()->assetTransforms->storeTransformIndexData($index);

				// Generate the transform
				craft()->assetTransforms->generateTransform($index);

				// Update the index
				$index->fileExists = true;
				craft()->assetTransforms->storeTransformIndexData($index);

				// Return the transform URL
				return craft()->assetTransforms->getUrlForTransformByTransformIndex($index);
			}
			else
			{
				// Queue up a new Generate Pending Transforms task, if there isn't one already
				if (!craft()->tasks->areTasksPending('GeneratePendingTransforms'))
				{
					craft()->tasks->createTask('GeneratePendingTransforms');
				}

				// Return the temporary transform URL
				return UrlHelper::getResourceUrl('transforms/'.$index->id);
			}
		}
	}

	/**
	 * Return true if user has permission to perform the action on the folder.
	 *
	 * @param $folderId
	 * @param $action
	 *
	 * @return bool
	 */
	public function canUserPerformAction($folderId, $action)
	{
		try
		{
			$this->checkPermissionByFolderIds($folderId, $action);
			return true;
		}
		catch (Exception $exception)
		{
			return false;
		}
	}

	/**
	 * Check for a permission on a source by a folder id or an array of folder ids.
	 *
	 * @param $folderIds
	 * @param $permission
	 *
	 * @throws Exception
	 * @return null
	 */
	public function checkPermissionByFolderIds($folderIds, $permission)
	{
		if (!is_array($folderIds))
		{
			$folderIds = array($folderIds);
		}

		foreach ($folderIds as $folderId)
		{
			$folderModel = $this->getFolderById($folderId);

			if (!$folderModel)
			{
				throw new Exception(Craft::t('That folder does not seem to exist anymore. Re-index the Assets source and try again.'));
			}

			if (
				!craft()->userSession->checkPermission($permission.':'.$folderModel->sourceId)
				&&
				!craft()->userSession->checkAuthorization($permission.':'.$folderModel->id))
			{
				throw new Exception(Craft::t('You don’t have the required permissions for this operation.'));
			}
		}
	}

	/**
	 * Check for a permission on a source by a file id or an array of file ids.
	 *
	 * @param $fileIds
	 * @param $permission
	 *
	 * @throws Exception
	 * @return null
	 */
	public function checkPermissionByFileIds($fileIds, $permission)
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		foreach ($fileIds as $fileId)
		{
			$file = $this->getFileById($fileId);

			if (!$file)
			{
				throw new Exception(Craft::t('That file does not seem to exist anymore. Re-index the Assets source and try again.'));
			}

			if (!craft()->userSession->checkPermission($permission.':'.$file->sourceId))
			{
				throw new Exception(Craft::t('You don’t have the required permissions for this operation.'));
			}
		}
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeUploadAsset' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeUploadAsset(Event $event)
	{
		$this->raiseEvent('onBeforeUploadAsset', $event);
	}

	/**
	 * Fires an 'onBeforeSaveAsset' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveAsset(Event $event)
	{
		$this->raiseEvent('onBeforeSaveAsset', $event);
	}

	/**
	 * Fires an 'onSaveAsset' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveAsset(Event $event)
	{
		$this->raiseEvent('onSaveAsset', $event);
	}

	/**
	 * Fires an 'onBeforeReplaceFile' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeReplaceFile(Event $event)
	{
		$this->raiseEvent('onBeforeReplaceFile', $event);
	}

	/**
	 * Fires an 'onReplaceFile' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onReplaceFile(Event $event)
	{
		$this->raiseEvent('onReplaceFile', $event);
	}

	/**
	 * Fires an 'onSaveFileContent' event.
	 *
	 * @param Event $event
	 *
	 * @deprecated Deprecated in 2.0. Use {@link onSaveAsset() `assets.onSaveAsset`} instead.
	 * @return null
	 */
	public function onSaveFileContent(Event $event)
	{
		craft()->deprecator->log('AssetsService::onSaveFileContent()', 'The assets.onSaveFileContent event has been deprecated. Use assets.onSaveAsset instead.');
		$this->raiseEvent('onSaveFileContent', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteAsset' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteAsset(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteAsset', $event);
	}

	/**
	 * Fires an 'onDeleteAsset' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeleteAsset(Event $event)
	{
		$this->raiseEvent('onDeleteAsset', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object prepped for retrieving assets.
	 *
	 * @return DbCommand
	 */
	private function _createFolderQuery()
	{
		return craft()->db->createCommand()
			->select('id, parentId, sourceId, name, path')
			->from('assetfolders');
	}

	/**
	 * Return the folder tree form a list of folders.
	 *
	 * @param $folders
	 *
	 * @return array
	 */
	private function _getFolderTreeByFolders($folders)
	{
		$tree = array();
		$referenceStore = array();

		foreach ($folders as $folder)
		{
			if ($folder->parentId && isset($referenceStore[$folder->parentId]))
			{
				$referenceStore[$folder->parentId]->addChild($folder);
			}
			else
			{
				$tree[] = $folder;
			}

			$referenceStore[$folder->id] = $folder;
		}

		$sort = array();

		foreach ($tree as $topFolder)
		{
			$sort[] = craft()->assetSources->getSourceById($topFolder->sourceId)->sortOrder;
		}

		array_multisort($sort, $tree);

		return $tree;
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for folders.
	 *
	 * @param DbCommand           $query
	 * @param FolderCriteriaModel $criteria
	 *
	 * @return null
	 */
	private function _applyFolderConditions($query, FolderCriteriaModel $criteria)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('f.id', $criteria->id, $whereParams);
		}

		if ($criteria->sourceId)
		{
			$whereConditions[] = DbHelper::parseParam('f.sourceId', $criteria->sourceId, $whereParams);
		}

		if ($criteria->parentId)
		{
			$whereConditions[] = DbHelper::parseParam('f.parentId', $criteria->parentId, $whereParams);
		}

		if ($criteria->name)
		{
			$whereConditions[] = DbHelper::parseParam('f.name', $criteria->name, $whereParams);
		}

		if (!is_null($criteria->path))
		{
			// This folder has a comma in it.
			if (strpos($criteria->path, ',') !== false)
			{
				// Escape the comma.
				$condition = DbHelper::parseParam('f.path', str_replace(',', '\,', $criteria->path), $whereParams);
				$lastKey = key(array_slice($whereParams, -1, 1, true));

				// Now un-escape it.
				$whereParams[$lastKey] = str_replace('\,', ',', $whereParams[$lastKey]);
			}
			else
			{
				$condition = DbHelper::parseParam('f.path', $criteria->path, $whereParams);
			}

			$whereConditions[] = $condition;
		}

		if (count($whereConditions) == 1)
		{
			$query->where($whereConditions[0], $whereParams);
		}
		else
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Flag a file merge in progress.
	 *
	 * @return null
	 */
	private function _startMergeProcess()
	{
		$this->_mergeInProgress = true;
	}

	/**
	 * Flag a file merge no longer in progress.
	 *
	 * @return null
	 */
	private function _finishMergeProcess()
	{
		$this->_mergeInProgress = false;
	}

	/**
	 * Merge a conflicting uploaded file.
	 *
	 * @param string $conflictResolution  User response to conflict.
	 * @param int    $theNewFileId        The id of the new file that is conflicting.
	 * @param string $fileName            The filename that is in the conflict.
	 *
	 * @return AssetOperationResponseModel
	 */
	private function _mergeUploadedFiles($conflictResolution, $theNewFileId, $fileName)
	{

		$theNewFile = $this->getFileById($theNewFileId);
		$folder = $theNewFile->getFolder();
		$source = craft()->assetSources->getSourceTypeById($folder->sourceId);

		$fileId = null;

		switch ($conflictResolution)
		{
			case AssetConflictResolution::Replace:
			{
				// Replace the actual file
				$targetFile = $this->findFile(array(
					'folderId' => $folder->id,
					'filename' => $fileName
				));

				// If the file doesn't exist in the index, but just in the source,
				// quick-index it, so we have a File Model to work with.
				if (!$targetFile)
				{
					$targetFile = new AssetFileModel();
					$targetFile->sourceId = $folder->sourceId;
					$targetFile->folderId = $folder->id;
					$targetFile->filename = $fileName;
					$targetFile->kind = IOHelper::getFileKind(IOHelper::getExtension($fileName));
					$this->storeFile($targetFile);
				}

				$source->replaceFile($targetFile, $theNewFile);
				$fileId = $targetFile->id;
			}
			// Falling through to delete the file
			case AssetConflictResolution::Cancel:
			{
				$this->deleteFiles($theNewFileId);
				break;
			}
			default:
			{
				$fileId = $theNewFileId;
				break;
			}
		}

		$response = new AssetOperationResponseModel();
		$response->setSuccess();

		if ($fileId)
		{
			$response->setDataItem('fileId', $fileId);
			$response->setDataItem('filename', $theNewFile->filename);
		}

		return $response;
	}

	/**
	 * Move a file between sources.
	 *
	 * @param BaseAssetSourceType $originatingSource
	 * @param BaseAssetSourceType $targetSource
	 * @param AssetFileModel      $file
	 * @param AssetFolderModel    $folder
	 * @param string              $action
	 *
	 * @return AssetOperationResponseModel
	 */
	private function _moveFileBetweenSources(BaseAssetSourceType $originatingSource, BaseAssetSourceType $targetSource, AssetFileModel $file, AssetFolderModel $folder, $action = '')
	{
		$localCopy = $originatingSource->getLocalCopy($file);

		// File model will be updated in the process, but we need the old data in order to finalize the transfer.
		$oldFileModel = clone $file;

		$response = $targetSource->transferFileIntoSource($localCopy, $folder, $file, $action);

		if ($response->isSuccess())
		{
			// Use the previous data to clean up
			craft()->assetTransforms->deleteAllTransformData($oldFileModel);
			$originatingSource->finalizeTransfer($oldFileModel);
		}

		IOHelper::deleteFile($localCopy);

		return $response;
	}

	/**
	 * Do an upload conflict resolution with merging.
	 *
	 * @param string $conflictResolution User response to conflict.
	 * @param int    $theNewFileId       The id of the new file that is conflicting.
	 * @param string $fileName           Filename of the conflicting file.
	 *
	 * @return AssetOperationResponseModel
	 */
	private function _resolveUploadConflict($conflictResolution, $theNewFileId, $fileName)
	{
		$this->_startMergeProcess();
		$response =  $this->_mergeUploadedFiles($conflictResolution, $theNewFileId, $fileName);
		$this->_finishMergeProcess();

		return $response;
	}
}
