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
	 * @param array|FileRecord $attributes
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
	 * @return Asset
	 */
	public function getAssetsInAssetFolder($assetFolderId)
	{
		$assets = Asset::model()->findAllByAttributes(array(
			'folderId' => $assetFolderId,
		));

		return $assets;
	}

	/**
	 * Returns a file by its ID.
	 *
	 * @param $assetId
	 * @return Asset
	 */
	public function getFileById($fileId)
	{
		$fileRecord = AssetFileRecord::model()->findById($fileId);
		if ($fileRecord)
		{
			return $this->populateFile($fileRecord);
		}
	}

	// -------------------------------------------
	//  Folders
	// -------------------------------------------

	/**
	 * Populates a folder model.
	 *
	 * @param array|FolderRecord $attributes
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
	}
}
