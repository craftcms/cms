<?php
namespace Craft;

/**
 *
 */
class AssetsService extends BaseApplicationComponent
{

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
	 * Get files by a folder id.
	 *
	 * @param $folderId
	 * @param $offset
	 * @param $limit
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getFilesByFolderId($folderId, $offset = 0, $limit = null, $indexBy = null)
	{
		return $this->findFiles(array(
			'folderId' => $folderId,
			'limit' => $limit,
			'offset' => $offset,
			'indexBy'  => $indexBy,


		));
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
	 * Finds files that match a given criteria.
	 *
	 * @param mixed $criteria
	 * @return array
	 */
	public function findFiles($criteria = null)
	{
		if (!($criteria instanceof ElementCriteriaModel))
		{
			$criteria = craft()->elements->getCriteria(ElementType::Asset, $criteria);
		}

		$query = craft()->db->createCommand()
			->select('f.*')
			->from('assetfiles AS f');

		$this->_applyFileConditions($query, $criteria);

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

		return AssetFileModel::populateModels($result, $criteria->indexBy);
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

		$criteria->limit = 1;
		$criteria->indexBy = null;
		$files = $this->findFiles($criteria);

		if ($files)
		{
			return $files[0];
		}
	}

	/**
	 * Gets a file's content record by its file ID.
	 *
	 * @param int $fileId
	 * @return AssetContentRecord
	 */
	public function getFileContentRecordByFileId($fileId)
	{
		$contentRecord = AssetContentRecord::model()->findByAttributes(array(
			'fileId' => $fileId
		));

		if (!$contentRecord)
		{
			$contentRecord = new AssetContentRecord();
			$contentRecord->fileId = $fileId;
		}

		return $contentRecord;
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

		$query = craft()->db->createCommand()
			->select('count(id)')
			->from('assetfiles AS f');

		$this->_applyFileConditions($query, $criteria);

		return (int)$query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for folders.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 */
	private function _applyFileConditions($query, ElementCriteriaModel $criteria)
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
		if ($criteria->folderId)
		{
			$whereConditions[] = DbHelper::parseParam('f.folderId', $criteria->folderId, $whereParams);
		}
		if ($criteria->filename)
		{
			$whereConditions[] = DbHelper::parseParam('f.filename', $criteria->filename, $whereParams);
		}
		if ($criteria->kind)
		{
			$whereConditions[] = DbHelper::parseParam('f.kind', $criteria->kind, $whereParams);
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
	 * Stores a file.
	 *
	 * @param AssetFileModel $file
	 * @return bool
	 */
	public function storeFile(AssetFileModel $file)
	{
		if ($file->id)
		{
			$fileRecord = AssetFileRecord::model()->findById($file->id);

			if (!$fileRecord)
			{
				throw new Exception('No asset exists with the ID “{id}”', array('id' => $file->id));
			}
		}
		else
		{
			$elementRecord = new ElementRecord();
			$elementRecord->type = ElementType::Asset;
			$elementRecord->enabled = 1;
			$elementRecord->save();
			$fileRecord = new AssetFileRecord();
			$fileRecord->id = $elementRecord->id;
		}

		$fileRecord->sourceId     = $file->sourceId;
		$fileRecord->folderId     = $file->folderId;
		$fileRecord->filename     = $file->filename;
		$fileRecord->kind         = $file->kind;
		$fileRecord->size         = $file->size;
		$fileRecord->width        = $file->width;
		$fileRecord->height       = $file->height;
		$fileRecord->dateModified = $file->dateModified;

		if ($fileRecord->save())
		{
			if (!$file->id)
			{
				// Save the ID on the model now that we have it
				$file->id = $fileRecord->id;
			}

			return true;
		}
		else
		{
			$file->addErrors($fileRecord->getErrors());
			return false;
		}
	}

	/**
	 * Saves a file's content.
	 *
	 * @param AssetFileModel $file
	 * @return bool
	 */
	public function saveFileContent(AssetFileModel $file)
	{
		// TODO: translation support
		$fieldLayout = craft()->fields->getLayoutByType(ElementType::Asset);
		return craft()->elements->saveElementContent($file, $fieldLayout);
	}

	// -------------------------------------------
	//  Folders
	// -------------------------------------------

	/**
	 * Populates a folder model.
	 *
	 * @param array|AssetFolderRecord $attributes
	 * @return AssetFolderModel
	 */
	public function populateFolder($attributes)
	{
		$folder = AssetFolderModel::populateModel($attributes);
		return $folder;
	}

	/**
	 * Mass-populates folder models.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateFolders($data, $index = 'id')
	{
		$folders = array();

		foreach ($data as $attributes)
		{
			$folder = $this->populateFolder($attributes);
			$folders[$folder->$index] = $folder;
		}

		return $folders;
	}

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
		$record->fullPath = $folderModel->fullPath;
		$record->save();

		return $record->id;
	}

	/**
	 * Get the folder tree for Assets.
	 *
	 * @return array
	 */
	public function getFolderTree()
	{
		$folders = $this->findFolders(array('order' => 'fullPath'));
		$tree = array();
		$referenceStore = array();

		foreach ($folders as $folder)
		{
			if ($folder->parentId)
			{
				$referenceStore[$folder->parentId]->addChild($folder);
			}
			else
			{
				$tree[] = $folder;
			}

			$referenceStore[$folder->id] = $folder;
		}

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
				throw new Exception(Craft::t("Can't find the parent folder!"));
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
				throw new Exception(Craft::t("Can't find the folder to rename!"));
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
	 * Delete a folder by it's id.
	 *
	 * @param $folderId
	 * @return AssetOperationResponseModel
	 * @throws Exception
	 */
	public function deleteFolder($folderId)
	{
		try
		{
			$folder = $this->getFolderById($folderId);
			if (empty($folder))
			{
				throw new Exception(Craft::t("Can't find the folder!"));
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
		$folderRecord = AssetFolderRecord::model()->findById($folderId);

		if ($folderRecord)
		{
			return $this->populateFolder($folderRecord);
		}

		return null;
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

		return $this->populateFolders($result);
	}

	/**
	 * Find a folder's child folders.
	 *
	 * @param AssetFolderModel $folderModel
	 * @return array
	 */
	public function findChildFolders(AssetFolderModel $folderModel)
	{
		$query = blx()->db->createCommand()
			->select('f.*')
			->from('assetfolders AS f')
			->where(array('like', 'fullPath', $folderModel->fullPath.'%'))
			->andWhere('sourceId = :sourceId', array(':sourceId' => $folderModel->sourceId));

		return $this->populateFolders($query->queryAll());
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

		if (!is_null($criteria->fullPath))
		{
			$whereConditions[] = DbHelper::parseParam('f.fullPath', $criteria->fullPath, $whereParams);
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

		switch ($userResponse)
		{
			case AssetsHelper::ActionReplace:
			{
				// Replace the actual file
				$targetFile = $this->findFile(array(
					'folderId' => $folderId,
					'filename' => $fileName
				));

				$replaceWith = craft()->assets->getFileById($createdFileId);

				$source->replaceFile($targetFile, $replaceWith);
			}
			// Falling through to delete the file
			case AssetsHelper::ActionCancel:
			{
				$this->deleteFiles($createdFileId);
				break;
			}
		}

		$response = new AssetOperationResponseModel();
		$response->setSuccess();

		return $response;
	}

	/**
	 * Delete a list of files by an array of ids (or a single id)
	 * @param $fileIds
	 */
	public function deleteFiles($fileIds)
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		foreach ($fileIds as $fileId)
		{
			$file = $this->getFileById($fileId);
			$source = craft()->assetSources->getSourceTypeById($file->sourceId);
			$source->deleteFile($file);
		}
	}

	/**
	 * Delete a file record by id.
	 *
	 * @param $fileId
	 * @return bool
	 */
	public function deleteFileRecord($fileId)
	{
		return (bool) AssetFileRecord::model()->deleteAll('id = :fileId', array(':fileId' => $fileId));
	}

	/**
	* Delete a folder record by id.
	*
	* @param $fileId
	* @return bool
	*/
	public function deleteFolderRecord($folderId)
	{
		return (bool) AssetFolderRecord::model()->deleteAll('id = :folderId', array(':folderId' => $folderId));
	}
}
