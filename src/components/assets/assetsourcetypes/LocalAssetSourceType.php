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
	 * Starts an indexing session.
	 *
	 * @param $sessionId
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		$indexedFolderIds = array();

		$indexedFolderIds[blx()->assetIndexing->ensureTopFolder($this->model)] = true;

		$localPath = $this->getSettings()->path;
		$fileList = IOHelper::getFolderContents($localPath, true);
		$fileList = array_filter($fileList, function ($value) use ($localPath) {
			$path = substr($value, strlen($localPath));
			$segments = explode('/', $path);
			foreach ($segments as $segment)
			{
				if (isset($segment[0]) && $segment[0] == '_')
				{
					return false;
				}
			}
			return true;
		});

		$offset = 0;
		$total = 0;

		foreach ($fileList as $file)
		{
			if ( !preg_match(AssetsHelper::IndexSkipItemsPattern, $file))
			{
				if (is_dir($file))
				{
					$fullPath = rtrim(str_replace($this->getSettings()->path, '', $file), '/') . '/';
					$folderId = $this->_ensureFolderByFulPath($fullPath);
					$indexedFolderIds[$folderId] = true;
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
		}

		$missingFolders = $this->_getMissingFolders($indexedFolderIds);

		return array('sourceId' => $this->model->id, 'total' => $total, 'missingFolders' => $missingFolders);
	}

	/**
	 * Process an indexing session.
	 *
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

		// This is the part of the path that actually matters
		$uriPath = substr($file, strlen($uploadPath));

		$fileModel = $this->_indexFile($uriPath);

		if ($fileModel)
		{
			blx()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;
			$fileModel->dateModified = IOHelper::getLastTimeModified($indexEntryModel->uri);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = getimagesize($indexEntryModel->uri);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			blx()->assets->storeFile($fileModel);

			return $fileModel->id;
		}

		return false;
	}

	/**
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $filePath
	 * @param $fileName
	 * @return string
	 * @throws Exception
	 */
	protected function _insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName)
	{

		$targetFolder = $this->getSettings()->path . $folder->fullPath;

		// Make sure the folder is writable
		if (! IOHelper::isWritable($targetFolder))
		{
			throw new Exception(Blocks::t('Target destination is not writable'));
		}

		$fileName = IOHelper::cleanFilename($fileName);

		$targetPath = $targetFolder . $fileName;
		$extension = IOHelper::getExtension($fileName);

		if (! IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Blocks::t('This file type is not allowed'));
		}

		if (IOHelper::fileExists($targetPath))
		{
			/*$response = new AssetOperationResponseModel();
			$response->setResponse(AssetOperationResponseModel::StatusConflict);
			$response->setResponseDataItem('prompt', $this->_getUserPromptOptions($fileName));
			return $response;*/
			// TODO handle the conflict instead of just saving as new
			$targetPath = $targetFolder . $this->_getNameReplacement($folder, $fileName);
			if (!$targetPath)
			{
				throw new Exception(Blocks::t('Could not find a suitable replacement name for file'));
			}
		}

		if (! IOHelper::copyFile($filePath, $targetPath))
		{
			throw new Exception(Blocks::t('Could not copy file to target destination'));
		}

		IOHelper::changePermissions($targetPath, IOHelper::writableFilePermissions);

		/*$response = new AssetOperationResponseModel();
		$response->setResponse(AssetOperationResponseModel::StatusSuccess);
		$response->setResponseDataItem('file_path', $targetPath);
		return $response;*/
		return $targetPath;

	}

	/**
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder
	 * @param $fileName
	 * @return string
	 */
	protected function _getNameReplacement(AssetFolderModel $folder, $fileName)
	{
		$fileList = IOHelper::getFolderContents($this->getSettings()->path . $folder->fullPath, false);
		$existingFiles = array();

		foreach ($fileList as $file)
		{
			$existingFiles[pathinfo($file, PATHINFO_BASENAME)] = true;
		}

		$fileParts = explode(".", $fileName);
		$extension = array_pop($fileParts);
		$fileName = join(".", $fileParts);

		for ($i = 1; $i <= 50; $i++)
		{
			if (!isset($existingFiles[$fileName . '_' . $i . '.' . $extension]))
			{
				return $fileName . '_' . $i . '.' . $extension;
			}
		}

		return false;
	}

	/**
	 * Get the timestamp of when a file size was last modified.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $sizeHandle
	 * @return mixed
	 */
	public function getTimeSizeModified(AssetFileModel $fileModel, $sizeHandle)
	{
		$path = $this->_getImageServerPath($fileModel, $sizeHandle);
		if (!IOHelper::fileExists($path))
		{
			return false;
		}
		return IOHelper::getLastTimeModified($path);
	}

	/**
	 * Put an image size for the File and handle using the provided path to the source image.
	 *
	 * @param AssetFileModel $fileModel
	 * @param $handle
	 * @param $sourceImage
	 * @return mixed
	 */
	public function putImageSize(AssetFileModel $fileModel, $handle, $sourceImage)
	{
		return IOHelper::copyFile($sourceImage, $this->_getImageServerPath($fileModel, $handle));
	}

	/**
	 * Get the image source path with the optional handle name.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $handle
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $fileModel, $handle = '')
	{
		return $this->_getImageServerPath($fileModel, $handle);
	}

	/**
	 * Get the local path for an image, opt	ionally with a size handle.
	 *
	 * @param AssetFileModel $fileModel
	 * @param string $handle
	 * @return string
	 */
	private function _getImageServerPath(AssetFileModel $fileModel, $handle = '')
	{
		$targetFolder = $this->getSettings()->path.$fileModel->getFolder()->fullPath;
		$targetFolder .= !empty($handle) ? '_'.$handle.'/': '';
		return $targetFolder.$fileModel->filename;
	}
}
