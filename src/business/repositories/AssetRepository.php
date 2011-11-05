<?php

class AssetRepository implements IAssetRepository
{
	public function getUploadFoldersBySiteId($siteId)
	{
		$uploadFolders = UploadFolders::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $uploadFolders;
	}

	public function getFilesForUploadFolder($uploadFolderId)
	{
		$files = Assets::model()->findAllByAttributes(array(
			'upload_folder_id' => $uploadFolderId,
		));

		return $files;
	}

	public function getUploadFolderById($uploadFolderId)
	{
		$folder = UploadFolders::model()->findByAttributes(array(
			'upload_folder_id' => $uploadFolderId,
		));

		return $folder;
	}

	public function getAllFilesBySiteId($siteId)
	{
		$prefix = Blocks::app()->configRepo->getDatabaseTablePrefix().'_';
		$pages = Blocks::app()->db->createCommand()
			->select('a.*')
			->from($prefix.'assets a')
			->join($prefix.'uploadfolders uf', 'a.upload_folder_id = uf.id')
			->join($prefix.'sites s', 'uf.site_id = s.id')
			->where('s.id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $pages;
	}

	public function getFileById($fileId)
	{
		$file = Assets::model()->findByAttributes(array(
			'id' => $fileId,
		));

		return $file;
	}
}
