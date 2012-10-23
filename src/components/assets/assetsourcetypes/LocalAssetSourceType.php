<?php
namespace Blocks;

/**
 * Local source type class
 */
class LocalAssetSourceType extends BaseAssetSourceType
{
	/**
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Blocks::t('Local Folder');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'path' => array(AttributeType::String, 'required' => true),
			'url'  => array(AttributeType::String, 'required' => true, 'label' => 'URL'),
		);
	}

	/**
	 * Returns the component's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return blx()->templates->render('_components/assetsourcetypes/Local/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * Starts an indexing session
	 * @param $sessionId
	 * @return array
	 */
	public function startIndex($sessionId)
	{

		$offset = 0;
		$indexedFolderIds = array();

		$indexedFolderIds[blx()->assetIndexing->ensureTopFolder($this->model)] = true;

		$fileList = IOHelper::getFolderContents($this->getSettings()->path);
		$total = 0;

		foreach ($fileList as $file)
		{
			if (is_dir($file))
			{
				$fullPath = rtrim(str_replace($this->getSettings()->path, '', $file), '/') . '/';
				$parameters = new FolderParams(
					array(
						'fullPath' => $fullPath,
						'sourceId' => $this->model->id
					)
				);

				$folderModel = blx()->assets->getFolder($parameters);

				// if we don't have a folder matching these, create a new one
				if (is_null($folderModel))
				{
					$parts = explode('/', rtrim($fullPath, '/'));
					$folderName = array_pop($parts);

					if (empty($parts))
					{
						$parameters->fullPath = "";
					}
					else
					{
						$parameters->fullPath = join('/', $parts) . '/';
					}

					// look up the parent folder
					$parentFolder = blx()->assets->getFolder($parameters);
					if (is_null($parentFolder))
					{
						$parentId = null;
					}
					else
					{
						$parentId = $parentFolder->id;
					}

					$folderModel = new AssetFolderModel();
					$folderModel->sourceId = $this->model->id;
					$folderModel->parentId = $parentId;
					$folderModel->name = $folderName;
					$folderModel->fullPath = $fullPath;
					$folderId = blx()->assets->storeFolder($folderModel);
					$indexedFolderIds[$folderId] = true;
				}
				else
				{
					$indexedFolderIds[$folderModel->id] = true;
				}
			}
			else
			{
				$indexEntry = array(
					'sourceId' => $this->model->id,
					'sessionId' => $sessionId,
					'offset' => $offset++,
					'uri' => $file,
					'size' => is_dir($file) ? 0 : filesize($file)
				);
				blx()->assetIndexing->storeIndexEntry($indexEntry);
				$total++;
			}
		}

		// figure out the obsolete records for folders
		$missingFolderIds = array();
		$parameters = new FolderParams(array(
			'sourceId' => $this->model->id
		));

		$allFolders = blx()->assets->getFolders($parameters);

		foreach ($allFolders as $folderModel)
		{
			if (!isset($indexedFolderIds[$folderModel->id]))
			{
				$missingFolderIds[$folderModel->id] = $this->model->name . '/' . $folderModel->fullPath;
			}
		}

		return array('source_id' => $this->model->id, 'total' => $total, 'missing_folders' => $missingFolderIds);
	}

	/**
	 * Process an indexing session
	 * @param $sessionId
	 * @param $offset
	 * @return mixed
	 */
	public function processIndex($sessionId, $offset)
	{
		$indexEntryModel = blx()->assetIndexing->getIndexEntry($this->model->id, $sessionId, $offset);

		if (empty($indexEntryModel))
		{
			return false;
		}

		$uploadPath = $this->getSettings()->path;

		$file = $indexEntryModel->uri;

		// this is the part of the path that actually matters
		$uriPath = substr($file, strlen($uploadPath));

		$fileIndexed = false;
		$extension = pathinfo($file, PATHINFO_EXTENSION);

		if (IOHelper::isExtensionAllowed($extension))
		{
			$parts = explode('/', $uriPath);
			$fileName = array_pop($parts);

			$searchFullPath = join('/', $parts) . (empty($parts) ? '' : '/');

			$folderParameters = new FolderParams(
				array(
					'sourceId' => $this->model->id,
					'fullPath' => $searchFullPath
				)
			);

			$parentFolder = blx()->assets->getFolder($folderParameters);

			if (empty($parentFolder))
			{
				return false;
			}

			$folderId = $parentFolder->id;

			$fileParameters = new FileParams(
				array(
					'folderId' => $folderId,
					'filename' => $fileName
				)
			);

			$fileModel = blx()->assets->getFile($fileParameters);

			if (is_null($fileModel))
			{
				$fileModel = new AssetFileModel();
				$fileModel->sourceId = $this->model->id;
				$fileModel->folderId = $folderId;
				$fileModel->filename = $fileName;
				$fileModel->kind = IOHelper::getFileKind($extension);
				$fileId = blx()->assets->storeFile($fileModel);
				$fileModel->id = $fileId;
			}
			else
			{
				$fileId = $fileModel->id;
			}

			blx()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileId);
			$fileIndexed = $fileId;
		}

		if ($fileIndexed && !empty($fileModel))
		{
			$fileModel->size = $indexEntryModel->size;
			$fileModel->dateModified = filemtime($indexEntryModel->uri);
			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($indexEntryModel->uri);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			blx()->assets->storeFile($fileModel);
		}

		return true;
	}

}
