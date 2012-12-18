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
	 * Get files by folder id
	 * @param $folderId
	 * @return array
	 */
	public function getFilesByFolderId($folderId)
	{
		return $this->getFiles(new FileCriteria(array('folderId' => $folderId)));
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

		return $this->getFile($parameters);
	}


	/**
	 * Gets files by parameters.
	 *
	 * @param FileCriteria|null $params
	 * @return array
	 */
	public function getFiles(FileCriteria $params = null)
	{
		if (!$params)
		{
			$params = new FileCriteria();
		}

		$query = blx()->db->createCommand()
			->select('f.*')
			->from('assetfiles AS f');

		$this->_applyFileConditions($query, $params);

		if ($params->order)
		{
			$query->order($params->order);
		}

		if ($params->offset)
		{
			$query->offset($params->offset);
		}

		if ($params->limit)
		{
			$query->limit($params->limit);
		}

		$result = $query->queryAll();

		return $this->populateFiles($result);
	}

	/**
	 * Get a single folder by params
	 * @param FileCriteria $params
	 * @return AssetFileModel|null
	 */
	public function getFile(FileCriteria $params = null)
	{
		$params->limit = 1;
		$file = $this->getFiles($params);

		if (is_array($file) && !empty($file))
		{
			return array_pop($file);
		}

		return null;
	}

	/**
	 * Gets the total number of files.
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
	 * @param           $params
	 * @param array     $params
	 */
	private function _applyFileConditions($query, $params)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($params->id)
		{
			$whereConditions[] = DbHelper::parseParam('f.id', $params->id, $whereParams);
		}
		if ($params->sourceId)
		{
			$whereConditions[] = DbHelper::parseParam('f.sourceId', $params->sourceId, $whereParams);
		}
		if ($params->folderId)
		{
			$whereConditions[] = DbHelper::parseParam('f.folderId', $params->folderId, $whereParams);
		}
		if ($params->filename)
		{
			$whereConditions[] = DbHelper::parseParam('f.filename', $params->filename, $whereParams);
		}
		if ($params->kind)
		{
			$whereConditions[] = DbHelper::parseParam('f.kind', $params->kind, $whereParams);
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
	 * Gets folders.
	 *
	 * @param FolderCriteria|null $params
	 * @return array
	 */
	public function getFolders(FolderCriteria $params = null)
	{
		if (!$params)
		{
			$params = new FolderCriteria();
		}

		$query = blx()->db->createCommand()
			->select('f.*')
			->from('assetfolders AS f');

		$this->_applyFolderConditions($query, $params);

		if ($params->order)
		{
			$query->order($params->order);
		}

		if ($params->offset)
		{
			$query->offset($params->offset);
		}

		if ($params->limit)
		{
			$query->limit($params->limit);
		}

		$result = $query->queryAll();

		return $this->populateFolders($result);
	}

	/**
	 * Get a single folder by params
	 * @param FolderCriteria $params
	 * @return AssetFolderModel|null
	 */
	public function getFolder(FolderCriteria $params = null)
	{
		$params->limit = 1;
		$folder = $this->getFolders($params);

		if (is_array($folder) && !empty($folder))
		{
			return array_pop($folder);
		}

		return null;
	}

	/**
	 * Gets the total number of folders.
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
	 * @param           $params
	 * @param array     $params
	 */
	private function _applyFolderConditions($query, $params)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($params->id)
		{
			$whereConditions[] = DbHelper::parseParam('f.id', $params->id, $whereParams);
		}

		if ($params->sourceId)
		{
			$whereConditions[] = DbHelper::parseParam('f.sourceId', $params->sourceId, $whereParams);
		}

		if ($params->name)
		{
			$whereConditions[] = DbHelper::parseParam('f.name', $params->name, $whereParams);
		}

		if (!is_null($params->fullPath))
		{
			$whereConditions[] = DbHelper::parseParam('f.fullPath', $params->fullPath, $whereParams);
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
