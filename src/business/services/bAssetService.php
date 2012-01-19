<?php

/**
 *
 */
class bAssetService extends CApplicationComponent
{
	/**
	 * @param $siteId
	 * @return bAssetFolder
	 */
	public function getAssetFoldersBySiteId($siteId)
	{
		$asssetFolders = bAssetFolder::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $asssetFolders;
	}

	/**
	 * @param $assetFolderId
	 * @return Asset
	 */
	public function getAssetsInAssetFolder($assetFolderId)
	{
		$assets = bAsset::model()->findAllByAttributes(array(
			'folder_id' => $assetFolderId,
		));

		return $assets;
	}

	/**
	 * @param $assetFolderId
	 * @return bAssetFolder
	 */
	public function getAssetFolderById($assetFolderId)
	{
		$folder = bAssetFolder::model()->findByAttributes(array(
			'asset_folder_id' => $assetFolderId,
		));

		return $folder;
	}

	/**
	 * @param $siteId
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
	 * @param $assetId
	 * @return Assets
	 */
	public function getAssetById($assetId)
	{
		$asset = bAsset::model()->findByAttributes(array(
			'id' => $assetId,
		));

		return $asset;
	}
}
