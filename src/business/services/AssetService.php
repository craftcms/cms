<?php

/**
 *
 */
class AssetService extends CApplicationComponent
{
	/**
	 * @access public
	 *
	 * @param $siteId
	 *
	 * @return AssetFolders
	 */
	public function getAssetFoldersBySiteId($siteId)
	{
		$asssetFolders = AssetFolders::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $asssetFolders;
	}

	/**
	 * @access public
	 *
	 * @param $assetFolderId
	 *
	 * @return Assets
	 */
	public function getAssetsInAssetFolder($assetFolderId)
	{
		$assets = Assets::model()->findAllByAttributes(array(
			'folder_id' => $assetFolderId,
		));

		return $assets;
	}

	/**
	 * @access public
	 *
	 * @param $assetFolderId
	 *
	 * @return AssetFolders
	 */
	public function getAssetFolderById($assetFolderId)
	{
		$folder = AssetFolders::model()->findByAttributes(array(
			'asset_folder_id' => $assetFolderId,
		));

		return $folder;
	}

	/**
	 * @access public
	 *
	 * @param $siteId
	 *
	 * @return Assets
	 */
	public function getAllAssetsBySiteId($siteId)
	{
		$assets = Blocks::app()->db->createCommand()
			->select('a.*')
			->from('{{assets}} a')
			->join('{{assetfolders}} af', 'a.asset_folder_id = af.id')
			->join('{{sites}} s', 'af.site_id = s.id')
			->where('s.id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $assets;
	}

	/**
	 * @access public
	 *
	 * @param $assetId
	 *
	 * @return Assets
	 */
	public function getAssetById($assetId)
	{
		$asset = Assets::model()->findByAttributes(array(
			'id' => $assetId,
		));

		return $asset;
	}
}
