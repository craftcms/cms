<?php
namespace Blocks;

/**
 *
 */
class AssetsService extends BaseApplicationComponent
{
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
	 * @param int $sourceid
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
	 * @param $assetFolderId
	 * @return array of AssetFileModel
	 */
	public function getAssetsInAssetFolder($assetFolderId)
	{
		$parameters = new FileParams(
			array(
				'folderId' => $assetFolderId
			)
		);

		return $this->getFiles($parameters);
	}

	/**
	 * Returns a file by its ID.
	 *
	 * @param $fileId
	 * @return AssetFileModel|null
	 */
	public function getFileById($fileId)
	{
		$parameters = new FileParams(
			array(
				'id' => $fileId
			)
		);

		return $this->getFile($parameters);
	}


	/**
	 * Gets files by parameters.
	 *
	 * @param FileParams|null $params
	 * @return array
	 */
	public function getFiles(FileParams $params = null)
	{
		if (!$params)
		{
			$params = new FileParams();
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
	 * @param FileParams $params
	 * @return AssetFileModel|null
	 */
	public function getFile(FileParams $params = null)
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
	 * @param FolderParams|null $params
	 * @return array
	 */
	public function getFolders(FolderParams $params = null)
	{
		if (!$params)
		{
			$params = new FolderParams();
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
	 * @param FolderParams $params
	 * @return AssetFolderModel|null
	 */
	public function getFolder(FolderParams $params = null)
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

		if ($params->fullPath)
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
}
