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
	 * @param string|null $localeId
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
	 * Saves the record for an asset.
	 *
	 * @param AssetFileModel $file
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

				// Fire an 'onBeforeSaveAsset' event
				$this->onBeforeSaveAsset(new Event($this, array(
					'asset'      => $file,
					'isNewAsset' => $isNewFile
				)));

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

					// Fire an 'onSaveAsset' event
					$this->onSaveAsset(new Event($this, array(
						'asset' => $file
					)));

					if ($this->hasEventHandler('onSaveFileContent'))
					{
						// Fire an 'onSaveFileContent' event (deprecated)
						$this->onSaveFileContent(new Event($this, array(
							'file' => $file
						)));
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
	 * Fires an 'onBeforeSaveAsset' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeSaveAsset(Event $event)
	{
		$this->raiseEvent('onBeforeSaveAsset', $event);
	}

	/**
	 * Fires an 'onSaveAsset' event.
	 *
	 * @param Event $event
	 */
	public function onSaveAsset(Event $event)
	{
		$this->raiseEvent('onSaveAsset', $event);
	}

	/**
	 * Fires an 'onSaveFileContent' event.
	 *
	 * @param Event $event
	 * @deprecated Deprecated in 2.0.
	 */
	public function onSaveFileContent(Event $event)
	{
		craft()->deprecator->log('AssetsService::onSaveFileContent()', 'The assets.onSaveFileContent event has been deprecated. Use assets.onSaveAsset instead.');
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
			$folderName = 'user_' . $userModel->id;
		}
		else
		{
			// A little obfuscation never hurt anyone
			$folderName = 'user_' . sha1(craft()->httpSession->getSessionID());
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
	 * @param $fileIds
	 * @param $folderId
	 * @param string $filename if this is a rename operation
	 * @param array $actions actions to take in case of a conflict.
	 * @return bool|AssetOperationResponseModel
	 * @throws Exception
	 */
	public function moveFiles($fileIds, $folderId, $filename = '', $actions = array())
	{
		if ($filename && is_array($fileIds) && count($fileIds) > 1)
		{
			throw new Exception(Craft::t("It's not possible to rename multiple files!"));
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
	 * @param AssetFileModel $file
	 * @param $filename
	 * @param string $action action to take in case of a conflict.
	 * @return bool|AssetOperationResponseModel
	 */
	public function renameFile(AssetFileModel $file, $filename, $action = "")
	{
		return $this->moveFiles(array($file->id), $file->folderId, $filename, $action);
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
			return AssetsHelper::generateUrl($sourceType, $file);
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
			if (craft()->config->get('generateTransformsBeforePageLoad'))
			{
				$existingTransformData->inProgress = true;
				craft()->assetTransforms->storeTransformIndexData($existingTransformData);

				craft()->assetTransforms->generateTransform($existingTransformData);

				$existingTransformData->fileExists = true;
				craft()->assetTransforms->storeTransformIndexData($existingTransformData);

				return craft()->assetTransforms->getUrlforTransformByFile($file, $transform);
			}
			else
			{
				return UrlHelper::getResourceUrl('transforms/'.$existingTransformData->id);
			}
		}
	}

	// Private methods

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

				// Now unescape it.
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
	 * Return true if user has permission to perform the action on the folder.
	 *
	 * @param $folderId
	 * @param $action
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
	 * @throws Exception
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

			if(!craft()->userSession->checkPermission($permission.':'.$folderModel->sourceId))
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
	 * @throws Exception
	 */
	public function checkPermissionByFileIds($fileIds, $permission)
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}
		foreach ($fileIds as $fileId)
		{
			$fileModel = $this->getFileById($fileId);
			if (!$fileModel)
			{
				throw new Exception(Craft::t('That file does not seem to exist anymore. Re-index the Assets source and try again.'));
			}
			if(!craft()->userSession->checkPermission($permission.':'.$fileModel->sourceId))
			{
				throw new Exception(Craft::t('You don’t have the required permissions for this operation.'));
			}
		}

	}

}
