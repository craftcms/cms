<?php
namespace Craft;

/**
 *
 */
class AssetsService extends BaseApplicationComponent
{
	private $_foldersById;
	private $_includedTransformLoader = false;

	/**
	 * A flag that designates that a file merge is in progress and name uniqueness should not be enforced
	 * @var bool
	 */
	private $_mergeInProgress = false;

	/**
	 * Returns all top-level files in a source.
	 *
	 * @param int $sourceId
	 * @param string|null $indexBy
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
	 * @param $fileId
	 * @return AssetFileModel|null
	 */
	public function getFileById($fileId)
	{
		return $this->findFile(array(
			'id' => $fileId
		));
	}

	/**
	 * Finds the first file that matches the given criteria.
	 *
	 * @param mixed $criteria
	 * @return AssetFileModel|null
	 */
	public function findFile($criteria = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria(ElementType::Asset, $criteria);
		}

		return $criteria->first();
	}

	/**
	 * Gets the total number of files that match a given criteria.
	 *
	 * @param mixed $criteria
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
	 * Stores a file.
	 *
	 * @param AssetFileModel $file
	 * @throws Exception
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
				throw new Exception(Craft::t("No asset exists with the ID “{id}”", array('id' => $file->id)));
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

		if (!$file->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				if ($isNewFile && !$file->getContent()->title)
				{
					// Give it a default title based on the file name
					$file->getContent()->title = str_replace('_', ' ', IOHelper::getFileName($file->filename, false));
				}

				// Save the element
				if (craft()->elements->saveElement($file, false))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewFile)
					{
						$fileRecord->id = $file->id;
					}

					// Save the file row
					$fileRecord->save(false);

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
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
		}

		return false;
	}

	/**
	 * Saves a file's content.
	 *
	 * @param AssetFileModel $file
	 * @param bool           $validate
	 * @return bool
	 */
	public function saveFileContent(AssetFileModel $file, $validate = true)
	{
		// TODO: translation support
		if (craft()->elements->saveElement($file, $validate))
		{
			// Fire an 'onSaveFileContent' event
			$this->onSaveFileContent(new Event($this, array(
				'file' => $file
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fires an 'onSaveFileContent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveFileContent(Event $event)
	{
		$this->raiseEvent('onSaveFileContent', $event);
	}

	// -------------------------------------------
	//  Folders
	// -------------------------------------------

	/**
	 * Store a folder by model and return the id
	 * @param AssetFolderModel $folderModel
	 * @return mixed
	 */
	public function storeFolder(AssetFolderModel $folderModel)
	{
		if (empty($folderModel->id))
		{
			$record = new AssetFolderRecord();
		}
		else
		{
			$record = AssetFolderRecord::model()->findById($folderModel->id);
		}

		$record->parentId = $folderModel->parentId;
		$record->sourceId = $folderModel->sourceId;
		$record->name = $folderModel->name;
		$record->path = $folderModel->path;
		$record->save();

		return $record->id;
	}

	/**
	 * Get the folder tree for Assets by source ids
	 *
	 * @param $allowedSourceIds
	 * @return array
	 */
	public function getFolderTreeBySourceIds($allowedSourceIds)
	{
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
	 * @return AssetFolderModel|null
	 * @throws Exception
	 */
	public function getUserFolder(UserModel $userModel)
	{
		$sourceTopFolder = craft()->assets->findFolder(array('sourceId' => FolderCriteriaModel::AssetsNoSource, 'parentId' => FolderCriteriaModel::AssetsNoParent));

		// Super unlikely, but would be very awkward if this happened without any contingency plans in place.
		if (!$sourceTopFolder)
		{
			$sourceTopFolder = new AssetFolderModel();
			$sourceTopFolder->name = TempAssetSourceType::sourceName;
			$sourceTopFolder->id = $this->storeFolder($sourceTopFolder);
		}

		$folderName = 'user_' . $userModel->id;
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
	 * Return the folder tree form a list of folders.
	 *
	 * @param $folders
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
	 * Create a folder by it's parent id and a folder name.
	 *
	 * @param $parentId
	 * @param $folderName
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
			$response = $source->renameFolder($folder, IOHelper::cleanFilename($newName));

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
	 * @return AssetOperationResponseModel
	 * @throws Exception
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
	 * @return AssetFolderModel|null
	 */
	public function getFolderById($folderId)
	{
		if (!isset($this->_foldersById) || !array_key_exists($folderId, $this->_foldersById))
		{
			$folderRecord = AssetFolderRecord::model()->findById($folderId);

			if ($folderRecord)
			{
				$this->_foldersById[$folderId] = AssetFolderModel::populateModel($folderRecord);
			}
			else
			{
				$this->_foldersById[$folderId] = null;
			}
		}

		return $this->_foldersById[$folderId];
	}

	/**
	 * Finds folders that match a given criteria.
	 *
	 * @param mixed $criteria
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

		$result = $query->queryAll();
		$folders = array();

		foreach ($result as $row)
		{
			$folder = AssetFolderModel::populateModel($row);
			$this->_foldersById[$folder->id] = $folder;
			$folders[] = $folder;
		}

		return $folders;
	}

	/**
	 * Find all folder's child folders in it's subtree.
	 *
	 * @param AssetFolderModel $folderModel
	 * @return array
	 */
	public function getAllDescendantFolders(AssetFolderModel $folderModel)
	{
		$query = craft()->db->createCommand()
			->select('f.*')
			->from('assetfolders AS f')
			->where(array('like', 'path', $folderModel->path.'%'))
			->andWhere('sourceId = :sourceId', array(':sourceId' => $folderModel->sourceId));

		$result = $query->queryAll();
		$folders = array();

		foreach ($result as $row)
		{
			$folder = AssetFolderModel::populateModel($row);
			$this->_foldersById[$folder->id] = $folder;
			$folders[$folder->id] = $folder;
		}

		return $folders;
	}

	/**
	 * Finds the first folder that matches a given criteria.
	 *
	 * @param mixed $criteria
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
	 * Gets the total number of folders that match a given criteria.
	 *
	 * @param mixed $criteria
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

	/**
	 * Applies WHERE conditions to a DbCommand query for folders.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param FolderCriteriaModel $criteria
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
			// Set sourceId to null if we're looking for folders with no source.
			if ($criteria->sourceId == FolderCriteriaModel::AssetsNoSource)
			{
				$criteria->sourceId = array(null);
			}

			$whereConditions[] = DbHelper::parseParam('f.sourceId', $criteria->sourceId, $whereParams);
		}

		if ($criteria->parentId)
		{
			// Set parentId to null if we're looking for folders with no parents.
			if ($criteria->parentId == FolderCriteriaModel::AssetsNoParent)
			{
				$criteria->parentId = null;
			}
			$whereConditions[] = DbHelper::parseParam('f.parentId', array($criteria->parentId), $whereParams);
		}

		if ($criteria->name)
		{
			$whereConditions[] = DbHelper::parseParam('f.name', $criteria->name, $whereParams);
		}

		if (!is_null($criteria->path))
		{
			$whereConditions[] = DbHelper::parseParam('f.path', $criteria->path, $whereParams);
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

	// -------------------------------------------
	//  File and folder managing
	// -------------------------------------------

	/**
	 * @param $folderId
	 * @param string $userResponse User response regarding filename conflict
	 * @param string $responseInfo Additional information about the chosen action
	 * @param string $fileName The filename that is in the conflict
	 *
	 * @return AssetOperationResponseModel
	 */
	public function uploadFile($folderId, $userResponse = '', $responseInfo = '', $fileName = '')
	{
		try
		{
			// handle a user's conflict resolution response
			if ( ! empty($userResponse))
			{
				$this->_startMergeProcess();
				$response =  $this->_mergeUploadedFiles($userResponse, $responseInfo, $fileName);
				$this->_finishMergeProcess();
				return $response;
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
	 * Inserts a file from a local path into a folder and returns the resultinf file id.
	 *
	 * @param $localPath
	 * @param $fileName
	 * @param $folderId
	 * @return bool|null
	 */
	public function insertFileByLocalPath($localPath, $fileName, $folderId)
	{
		$folder = $this->getFolderById($folderId);
		if (!$folder)
		{
			return false;
		}
		$source = craft()->assetSources->getSourceTypeById($folder->sourceId);
		$response = $source->insertFileByPath($localPath, $folder, $fileName, true);
		return $response->getDataItem('fileId');
	}

	/**
	 * Flag a file merge in progress.
	 */
	private function _startMergeProcess()
	{
		$this->_mergeInProgress = true;
	}

	/**
	 * Flag a file merge no longer in progress.
	 */
	private function _finishMergeProcess()
	{
		$this->_mergeInProgress = false;
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
	 * Merge a conflicting uploaded file.
	 *
	 * @param string $userResponse User response to conflict
	 * @param string $responseInfo Additional information about the chosen action
	 * @param string $fileName The filename that is in the conflict
	 * @return array|string
	 */
	private function _mergeUploadedFiles($userResponse, $responseInfo, $fileName)
	{
		list ($folderId, $createdFileId) = explode(":", $responseInfo);

		$folder = $this->getFolderById($folderId);
		$source = craft()->assetSources->getSourceTypeById($folder->sourceId);

		$fileId = null;

		switch ($userResponse)
		{
			case AssetsHelper::ActionReplace:
			{
				// Replace the actual file
				$targetFile = $this->findFile(array(
					'folderId' => $folderId,
					'filename' => $fileName
				));

				$replaceWith = $this->getFileById($createdFileId);

				$source->replaceFile($targetFile, $replaceWith);
				$fileId = $targetFile->id;
			}
			// Falling through to delete the file
			case AssetsHelper::ActionCancel:
			{
				$this->deleteFiles($createdFileId);
				break;
			}
			default:
			{
				$fileId = $createdFileId;
				break;
			}
		}

		$response = new AssetOperationResponseModel();
		$response->setSuccess();
		if ($fileId)
		{
			$response->setDataItem('fileId', $fileId);
		}

		return $response;
	}

	/**
	 * Delete a list of files by an array of ids (or a single id).
	 *
	 * @param $fileIds
	 * @return AssetOperationResponseModel
	 */
	public function deleteFiles($fileIds)
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
				$source->deleteFile($file);
				craft()->elements->deleteElementById($fileId);
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
	 * @param array $fileIds
	 * @param $folderId
	 * @param array $filenames if this is a rename operation
	 * @param array $actions actions to take in case of a conflict.
	 * @return bool|AssetOperationResponseModel
	 * @throws Exception
	 */
	public function moveFiles($fileIds, $folderId, $filenames = array(), $actions = array())
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		if (!is_array($actions))
		{
			$actions = array($actions);
		}

		if (!is_array($filenames))
		{
			$filenames = array($filenames);
		}

		$results = array();

		$response = new AssetOperationResponseModel();

		foreach ($fileIds as $i => $fileId)
		{
			$file = $this->getFileById($fileId);

			// If this is not a rename operation, then the filename remains the original
			$filename = isset($filenames[$i]) ? $filenames[$i] : '';

			if (empty($filename))
			{
				$filename = $file->filename;
			}

			$filename = IOHelper::cleanFilename($filename);

			if ($folderId == $file->folderId && ($filename == $file->filename))
			{
				$response = new AssetOperationResponseModel();
				$response->setSuccess();
				$results[] = $response;
			}

			$originalSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
			$folder = $this->getFolderById($folderId);
			$newSourceType = craft()->assetSources->getSourceTypeById($folder->sourceId);

			// Make sure that we're allowed to move this file
			if (!craft()->userSession->checkPermission('removeFromAssetSource:'.$originalSourceType->model->id)
				|| !craft()->userSession->checkPermission('uploadToAssetSource:'.$newSourceType->model->id))
			{
				throw new Exception(Craft::t("You don't have the required permissions for this operation."));
			}

			if ($originalSourceType && $newSourceType)
			{
				if ( !$response = $newSourceType->moveFileInsideSource($originalSourceType, $file, $folder, $filename, $actions[$i]))
				{
					$response = $this->_moveFileBetweenSources($originalSourceType, $newSourceType, $file, $folder, $actions[$i]);
				}
			}
			else
			{
				$response->setError(Craft::t("There was an error moving the file {file}.", array('file' => $file->filename)));
			}
		}

		return $response;
	}

	/**
	 * Move a file between sources.
	 *
	 * @param BaseAssetSourceType $originalSource
	 * @param BaseAssetSourceType $newSource
	 * @param AssetFileModel $file
	 * @param AssetFolderModel $folder
	 * @param string $action
	 * @return AssetOperationResponseModel
	 */
	private function _moveFileBetweenSources(BaseAssetSourceType $originalSource, BaseAssetSourceType $newSource, AssetFileModel $file, AssetFolderModel $folder, $action = '')
	{
		$localCopy = $originalSource->getLocalCopy($file);

		// File model will be updated in the process, but we need the old data in order to finalize the transfer.
		$oldFileModel = clone $file;

		$response = $newSource->transferFileIntoSource($localCopy, $folder, $file, $action);
		if ($response->isSuccess())
		{
			// Use the previous data to clean up
			$originalSource->deleteCreatedImages($oldFileModel);
			$originalSource->finalizeTransfer($oldFileModel);
			craft()->assetTransforms->deleteTransformRecordsByFileId($oldFileModel);
			IOHelper::deleteFile($localCopy);
		}

		return $response;
	}

	/**
	* Delete a folder record by id.
	*
	* @param $folderId
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
	 * @param $transform
	 * @return string
	 */
	public function getUrlForFile(AssetFileModel $file, $transform = null)
	{
		if (!$transform || !ImageHelper::isImageManipulatable(IOHelper::getExtension($file->filename)))
		{
			$sourceType = craft()->assetSources->getSourceTypeById($file->sourceId);
			$base = $sourceType->getBaseUrl();
			return $base.$file->getFolder()->path.$file->filename;
		}

		// Get the transform index model
		$existingTransformData  = craft()->assetTransforms->getTransformIndex($file, $transform);

		// Does the file actually exist?
		if ($existingTransformData->fileExists)
		{
			return craft()->assetTransforms->getUrlforTransformByFile($file, $transform);
		}
		else
		{
			if (craft()->config->get('generateTransformsAfterPageLoad'))
			{
				// File doesn't exist yet - load the TransformLoader and set the placeholder URL flag
				$placeholderUrl = UrlHelper::getResourceUrl('images/blank.gif');

				if (!$this->_includedTransformLoader)
				{
					$entityPlaceholderUrl = htmlspecialchars($placeholderUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
					$spinnerUrl = UrlHelper::getResourceurl('images/spinner_transform.gif');
					$actionUrl  = UrlHelper::getActionUrl('assets/generateTransform');

					craft()->templates->includeJsResource('js/TransformLoader.js');
					craft()->templates->includeJs('new TransformLoader(' .
						JsonHelper::encode($placeholderUrl).', ' .
						JsonHelper::encode($entityPlaceholderUrl).', ' .
						JsonHelper::encode($spinnerUrl).', ' .
						JsonHelper::encode($actionUrl) .
					');');

					$this->_includedTransformLoader = true;
				}

				return $placeholderUrl.'#'.$existingTransformData->id;
			}
			else
			{
				craft()->assetTransforms->updateTransforms($file, array($transform));
				return craft()->assetTransforms->getUrlforTransformByFile($file, $transform);
			}
		}
	}
}
