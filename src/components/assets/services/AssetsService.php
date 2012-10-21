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

		return AssetFileModel::populateModels($query, 'id');
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
			return AssetFileModel::populateModel($fileRecord);
		}
	}

	// -------------------------------------------
	//  Folders
	// -------------------------------------------

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
			return AssetFolderModel::populateModel($folderRecord);
		}
	}
}
