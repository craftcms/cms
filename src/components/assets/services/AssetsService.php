<?php
namespace Blocks;

/**
 *
 */
class AssetsService extends BaseApplicationComponent
{
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
	 * @param $assetFolderId
	 * @return AssetFolder
	 */
	public function getAssetFolderById($assetFolderId)
	{
		$folder = AssetFolder::model()->findByAttributes(array(
			'asset_folderId' => $assetFolderId,
		));

		return $folder;
	}

	/**
	 * @param $assetId
	 * @return Asset
	 */
	public function getAssetById($assetId)
	{
		$asset = Asset::model()->findByAttributes(array(
			'id' => $assetId,
		));

		return $asset;
	}
}
