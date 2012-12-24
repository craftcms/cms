<?php
namespace Blocks;

/**
 *
 */
class AssetsService extends BaseEntityService
{
	// -------------------------------------------
	//  Asset Blocks
	// -------------------------------------------

	/**
	 * The block model class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockModelClass = 'AssetBlockModel';

	/**
	 * The block record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $blockRecordClass = 'AssetBlockRecord';

	/**
	 * The content record class name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $contentRecordClass = 'AssetContentRecord';

	/**
	 * The name of the content table column right before where the block columns should be inserted.
	 *
	 * @access protected
	 * @var string
	 */
	protected $placeBlockColumnsAfter = 'fileId';

	// -------------------------------------------
	//  Files
	// -------------------------------------------

	/**
	 * Populates a file model.
	 *
	 * @param array|AssetFileRecord $attributes
	 * @return AssetFileModel
	 */
	public function populateFile($attributes)
	{
		$file = AssetFileModel::populateModel($attributes);
		return $file;
	}

	/**
	 * Mass-populates file models.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateFiles($data, $index = 'id')
	{
		$files = array();

		foreach ($data as $attributes)
		{
			$file = $this->populateFile($attributes);
			$files[$file->$index] = $file;
		}

		return $files;
	}

	/**
	 * Returns all top-level files in a source.
	 *
	 * @param int $sourceId
	 * @return array
	 */
	public function getFilesBySourceId($sourceId)
	{
		$query = blx()->db->createCommand()
			->select('fi.*')
			->from('assetfiles fi')
			->join('assetfolders fo', 'fo.id = fi.folderId')
			->where('fo.sourceId = :sourceId', array(':sourceId' => $sourceId))
			->order('fi.filename')
			->queryAll();

		return $this->populateFiles($query);
	}

	/**
	 * Get files by a folder id.
	 *
	 * @param $folderId
	 * @return array
	 */
	public function getFilesByFolderId($folderId)
	{
		return $this->findFiles(new FileCriteria(array('folderId' => $folderId)));
	}

	/**
	 * Returns a file by its ID.
	 *
	 * @param $fileId
	 * @return AssetFileModel|null
	 */
	public function getFileById($fileId)
	{
		$parameters = new FileCriteria(array('id' => $fileId));

		return $this->findFile($parameters);
	}

	/**
	 * Finds files that match a given criteria.
	 *
	 * @param FileCriteria|null $criteria
	 * @return array
	 */
	public function findFiles(FileCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new FileCriteria();
		}

		$query = blx()->db->createCommand()
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

		return $this->populateFiles($result);
	}

	/**
	 * Finds the first file that matches the given criteria.
	 *
	 * @param FileCriteria $criteria
	 * @return AssetFileModel|null
	 */
	public function findFile(FileCriteria $criteria = null)
	{
		$criteria->limit = 1;
		$file = $this->findFiles($criteria);

		if (is_array($file) && !empty($file))
		{
			return array_pop($file);
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
	 * @param FileCriteria|null $criteria
	 * @return int
	 */
	public function getTotalFiles(FileCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new FileCriteria();
		}

		$query = blx()->db->createCommand()
			->select('count(id)')
			->from('assetfiles AS f');

		$this->_applyFileConditions($query, $criteria);

		return (int) $query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for folders.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param FileCriteria $criteria
	 */
	private function _applyFileConditions($query, $criteria)
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
	 * Store a file by model and return the id
	 * @param AssetFileModel $fileModel
	 * @return mixed
	 */
	public function storeFile(AssetFileModel $fileModel)
	{

		if (empty($fileModel->id))
		{
			$record = new AssetFileRecord();
		}
		else
		{
			$record = AssetFileRecord::model()->findById($fileModel->id);
		}

		$record->sourceId = $fileModel->sourceId;
		$record->folderId = $fileModel->folderId;
		$record->filename = $fileModel->filename;
		$record->kind = $fileModel->kind;
		$record->size = $fileModel->size;
		$record->width = $fileModel->width;
		$record->height = $fileModel->height;
		$record->dateModified = $fileModel->dateModified;

		$record->save();

		return $record->id;
	}

	/**
	 * Store block's contents for a file.
	 *
	 * @param AssetFileModel $file
	 * @return bool
	 */
	public function storeFileBlocks(AssetFileModel $file)
	{

		$contentRecord = $this->getFileContentRecordByFileId($file->id);

		// Populate the blocks' content
		$blocks = $this->getAllBlocks();
		$blockTypes = array();

		foreach ($blocks as $block)
		{
			$blockType = blx()->blockTypes->populateBlockType($block);
			$blockType->entity = $file;

			if ($blockType->defineContentAttribute() !== false)
			{
				$handle = $block->handle;
				$contentRecord->$handle = $blockType->getPostData();
			}

			// Keep the block type instance around for calling onAfterEntitySave()
			$blockTypes[] = $blockType;
		}

		if ($contentRecord->save())
		{
			// Give the block types a chance to do any post-processing
			foreach ($blockTypes as $blockType)
			{
				$blockType->onAfterEntitySave();
			}

			return true;
		}
		else
		{
			$contentRecord->addErrors($contentRecord->getErrors());

			return false;
		}
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
	 * Delete a folder by it's model.
	 *
	 * @param AssetFolderModel $folder
	 */
	public function deleteFolder(AssetFolderModel $folder)
	{
		blx()->db->createCommand()->delete('assetfolders', array('id' => $folder->id));
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
	 * @param FolderCriteria|null $criteria
	 * @return array
	 */
	public function findFolders(FolderCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new FolderCriteria();
		}

		$query = blx()->db->createCommand()
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
	 * Finds the first folder that matches a given criteria.
	 *
	 * @param FolderCriteria $criteria
	 * @return AssetFolderModel|null
	 */
	public function findFolder(FolderCriteria $criteria = null)
	{
		$criteria->limit = 1;
		$folder = $this->findFolders($criteria);

		if (is_array($folder) && !empty($folder))
		{
			return array_pop($folder);
		}
	}

	/**
	 * Gets the total number of folders that match a given criteria.
	 *
	 * @param FolderCriteria|null $criteria
	 * @return int
	 */
	public function getTotalFolders(FolderCriteria $criteria = null)
	{
		if (!$criteria)
		{
			$criteria = new FolderCriteria();
		}

		$query = blx()->db->createCommand()
			->select('count(id)')
			->from('assetfolders AS f');

		$this->_applyFolderConditions($query, $criteria);

		return (int) $query->queryScalar();
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for folders.
	 *
	 * @access private
	 * @param DbCommand $query
	 * @param FolderCriteria $criteria
	 */
	private function _applyFolderConditions($query, $criteria)
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

 		if ($criteria->parentId || is_null($criteria->parentId))
		{
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
	 * @param $userResponse
	 * @return AssetFileModel|bool
	 */
	public function uploadFile($folderId, $userResponse)
	{
		try
		{
			// handle a user's conflict resolution response
			if (empty($userResponse))
			{
				//$this->_mergeUploadedFiles();
			}

			$folder = $this->getFolderById($folderId);

			$source = blx()->assetSources->getSourceTypeById($folder->sourceId);

			return $source->uploadFile($folder);
		}
		catch (Exception $exception)
		{
			return false;
			//$response = new AssetOperationResponseModel();
			//$response->setError(Blocks::t('Error uploading the file: {error}', array('error' => $exception->getMessage())));
		}

		//return $response;
	}
}
