<?php

class AssetService implements IAssetService
{
	public function getUploadFoldersBySiteId($siteId)
	{
		$uploadFolders = AssetFolders::model()->findAllByAttributes(array(
			'site_id' => $siteId,
		));

		return $uploadFolders;
	}

	public function getFilesForUploadFolder($uploadFolderId)
	{
		$files = Assets::model()->findAllByAttributes(array(
			'folder_id' => $uploadFolderId,
		));

		return $files;
	}

	public function getUploadFolderById($uploadFolderId)
	{
		$folder = AssetFolders::model()->findByAttributes(array(
			'upload_folder_id' => $uploadFolderId,
		));

		return $folder;
	}

	public function getAllFilesBySiteId($siteId)
	{
		$files = Blocks::app()->db->createCommand()
			->select('a.*')
			->from('{{assets}} a')
			->join('{{uploadfolders}} uf', 'a.upload_folder_id = uf.id')
			->join('{{sites}} s', 'uf.site_id = s.id')
			->where('s.id=:siteId', array(':siteId' => $siteId))
			->queryAll();

		return $files;
	}

	public function getFileById($fileId)
	{
		$file = Assets::model()->findByAttributes(array(
			'id' => $fileId,
		));

		return $file;
	}
}
