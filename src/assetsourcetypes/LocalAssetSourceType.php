<?php
namespace Craft;

/**
 * The local asset source type class. Handles the implementation of the local filesystem as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 * @deprecated This class will be removed in Craft 3.0.
 */
class LocalAssetSourceType extends BaseAssetSourceType
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	protected $isSourceLocal = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Local Folder');
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		return craft()->templates->render('_components/assetsourcetypes/Local/settings', array(
			'settings' => $this->getSettings()
		));
	}

	/**
	 * @inheritDoc ISavableComponentType::prepSettings()
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function prepSettings($settings)
	{
		// Add a trailing slash to the Path and URL settings
		$settings['path'] = !empty($settings['path']) ? rtrim($settings['path'], '/').'/' : '';
		$settings['url'] = !empty($settings['url']) ? rtrim($settings['url'], '/').'/' : '';

		return $settings;
	}

	/**
	 * @inheritDoc BaseAssetSourceType::startIndex()
	 *
	 * @param string $sessionId
	 *
	 * @return array
	 */
	public function startIndex($sessionId)
	{
		$indexedFolderIds = array();

		$indexedFolderIds[craft()->assetIndexing->ensureTopFolder($this->model)] = true;

		$localPath = $this->getSourceFileSystemPath();

		if ($localPath == '/' || !IOHelper::folderExists($localPath) || $localPath === false)
		{
			return array('sourceId' => $this->model->id, 'error' => Craft::t('The path of your source “{source}” appears to be invalid.', array('source' => $this->model->name)));
		}

		$fileList = IOHelper::getFolderContents($localPath, true);

		if ($fileList && is_array($fileList) && count($fileList) > 0)
		{
			$fileList = array_filter($fileList, function($value) use ($localPath)
			{
				$path = mb_substr($value, mb_strlen($localPath));
				$segments = explode('/', $path);

				// Ignore the file
				array_pop($segments);

				foreach ($segments as $segment)
				{
					if (isset($segment[0]) && $segment[0] == '_')
					{
						return false;
					}
				}

				return true;
			});
		}

		$offset = 0;
		$total = 0;

		foreach ($fileList as $file)
		{
			if (!preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $file))
			{
				if (is_dir($file))
				{
					$fullPath = rtrim(str_replace($this->getSourceFileSystemPath(), '', $file), '/').'/';
					$folderId = $this->ensureFolderByFullPath($fullPath);
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

					craft()->assetIndexing->storeIndexEntry($indexEntry);
					$total++;
				}
			}
		}

		$missingFolders = $this->getMissingFolders($indexedFolderIds);

		return array('sourceId' => $this->model->id, 'total' => $total, 'missingFolders' => $missingFolders);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::processIndex()
	 *
	 * @param string $sessionId
	 * @param int    $offset
	 *
	 * @return mixed
	 */
	public function processIndex($sessionId, $offset)
	{
		$indexEntryModel = craft()->assetIndexing->getIndexEntry($this->model->id, $sessionId, $offset);

		if (empty($indexEntryModel))
		{
			return false;
		}

		// Make sure we have a trailing slash. Some people love to skip those.
		$uploadPath = $this->getSourceFileSystemPath();

		$file = $indexEntryModel->uri;

		// This is the part of the path that actually matters
		$uriPath = mb_substr($file, mb_strlen($uploadPath));

		$fileModel = $this->indexFile($uriPath);

		if ($fileModel)
		{
			craft()->assetIndexing->updateIndexEntryRecordId($indexEntryModel->id, $fileModel->id);

			$fileModel->size = $indexEntryModel->size;
			$fileModel->dateModified = IOHelper::getLastTimeModified($indexEntryModel->uri);

			if ($fileModel->kind == 'image')
			{
				list ($width, $height) = ImageHelper::getImageSize($indexEntryModel->uri);

				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			craft()->assets->storeFile($fileModel);

			return $fileModel->id;
		}

		return false;
	}

	/**
	 * @inheritDoc BaseAssetSourceType::putImageTransform()
	 *
	 * @param AssetFileModel           $file
	 * @param AssetTransformIndexModel $index
	 * @param string                   $sourceImage
	 *
	 * @return mixed
	 */
	public function putImageTransform(AssetFileModel $file, AssetTransformIndexModel $index, $sourceImage)
	{
		$folder =  $this->getSourceFileSystemPath().$file->folderPath;
		$targetPath = $folder.craft()->assetTransforms->getTransformSubpath($file, $index);
		return IOHelper::copyFile($sourceImage, $targetPath, true);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::getImageSourcePath()
	 *
	 * @param AssetFileModel $file
	 *
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $file)
	{
		return $this->getSourceFileSystemPath().$file->getPath();
	}

	/**
	 * @inheritDoc BaseAssetSourceType::getLocalCopy()
	 *
	 * @param AssetFileModel $file
	 *
	 * @return mixed
	 */

	public function getLocalCopy(AssetFileModel $file)
	{
		$location = AssetsHelper::getTempFilePath($file->getExtension());
		IOHelper::copyFile($this->_getFileSystemPath($file), $location);
		clearstatcache();

		return $location;
	}

	/**
	 * @inheritDoc BaseAssetSourceType::fileExists()
	 *
	 * @param string $parentPath  Parent path
	 * @param string $filename    The name of the file.
	 *
	 * @return boolean
	 */
	public function fileExists($parentPath, $fileName)
	{
		return IOHelper::fileExists(rtrim($this->getSourceFileSystemPath().$parentPath, '/').'/'.$fileName);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::folderExists()
	 *
	 * @param string $parentPath  Parent path
	 * @param string $folderName
	 *
	 * @return boolean
	 */
	public function folderExists($parentPath, $folderName)
	{
		return IOHelper::folderExists($this->getSourceFileSystemPath().$parentPath.$folderName);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::getBaseUrl()
	 *
	 * @return string
	 */
	public function getBaseUrl()
	{
		$url = $this->getSettings()->url;

		return craft()->config->parseEnvironmentString($url);
	}

	/**
	 * Returns the source's base server path.
	 *
	 * @return string
	 */
	public function getBasePath()
	{
		$path = $this->getSettings()->path;

		return craft()->config->parseEnvironmentString($path);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseAssetSourceType::insertFileInFolder()
	 *
	 * @param AssetFolderModel $folder
	 * @param string           $filePath
	 * @param string           $fileName
	 *
	 * @throws Exception
	 * @return AssetOperationResponseModel
	 */
	protected function insertFileInFolder(AssetFolderModel $folder, $filePath, $fileName)
	{
		// Check if the set file system path exists
		$basePath = $this->getSourceFileSystemPath();

		if (empty($basePath))
		{
			$basePath = $this->getBasePath();

			if (!empty($basePath))
			{
				throw new Exception(Craft::t('The file system path “{folder}” set for this source does not exist.', array('folder' => $this->getBasePath())));
			}
		}

		$targetFolder = $this->getSourceFileSystemPath().$folder->path;

		// Make sure the folder exists.
		if (!IOHelper::folderExists($targetFolder))
		{
			throw new Exception(Craft::t('The folder “{folder}” does not exist.', array('folder' => $targetFolder)));
		}

		// Make sure the folder is writable
		if (!IOHelper::isWritable($targetFolder))
		{
			throw new Exception(Craft::t('The folder “{folder}” is not writable.', array('folder' => $targetFolder)));
		}

		$fileName = AssetsHelper::cleanAssetName($fileName);
		$targetPath = $targetFolder.$fileName;
		$extension = IOHelper::getExtension($fileName);

		if (!IOHelper::isExtensionAllowed($extension))
		{
			throw new Exception(Craft::t('This file type is not allowed'));
		}

		if (IOHelper::fileExists($targetPath))
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		if (! IOHelper::copyFile($filePath, $targetPath))
		{
			throw new Exception(Craft::t('Could not copy file to target destination'));
		}

		IOHelper::changePermissions($targetPath, craft()->config->get('defaultFilePermissions'));

		$response = new AssetOperationResponseModel();

		return $response->setSuccess()->setDataItem('filePath', $targetPath);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::getNameReplacementInFolder()
	 *
	 * @param AssetFolderModel $folder
	 * @param string           $fileName
	 *
	 * @return string
	 */
	protected function getNameReplacementInFolder(AssetFolderModel $folder, $fileName)
	{
		$fileList = IOHelper::getFolderContents($this->getSourceFileSystemPath().$folder->path, false);

		if (is_array($fileList))
		{
			foreach ($fileList as &$file)
			{
				$file = IOHelper::getFileName($file);
			}
		}
		else
		{
			throw new Exception(Craft::t('The folder “{folder}” cannot be read.', array('folder' => $this->getSourceFileSystemPath().$folder->path)));
		}

		return AssetsHelper::getFilenameReplacement($fileList, $fileName);
	}

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'path'       => array(AttributeType::String, 'required' => true),
			'publicURLs' => array(AttributeType::Bool,   'default' => true),
			'url'        => array(AttributeType::String, 'label' => 'URL'),
		);
	}

	/**
	 * Get the file system path for upload source.
	 *
	 * @param LocalAssetSourceType $sourceType The SourceType.
	 *
	 * @return string
	 */
	protected function getSourceFileSystemPath(LocalAssetSourceType $sourceType = null)
	{
		$path = is_null($sourceType) ? $this->getBasePath() : $sourceType->getBasePath();
		$path = IOHelper::getRealPath($path);

		return $path;
	}

	/**
	 * @inheritDoc BaseAssetSourceType::deleteSourceFile()
	 *
	 * @param string $subpath
	 *
	 * @return null
	 */
	protected function deleteSourceFile($subpath)
	{
		IOHelper::deleteFile($this->getSourceFileSystemPath().$subpath, true);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::moveSourceFile()
	 *
	 * @param AssetFileModel   $file
	 * @param AssetFolderModel $targetFolder
	 * @param string           $fileName
	 * @param bool             $overwrite
	 *
	 * @return mixed
	 */
	protected function moveSourceFile(AssetFileModel $file, AssetFolderModel $targetFolder, $fileName = '', $overwrite = false)
	{
		if (empty($fileName))
		{
			$fileName = $file->filename;
		}

		$newServerPath = $this->getSourceFileSystemPath().$targetFolder->path.$fileName;

		$conflictingRecord = craft()->assets->findFile(array(
			'folderId' => $targetFolder->id,
			'filename' => $fileName
		));

		$conflict = !$overwrite && (IOHelper::fileExists($newServerPath) || (!craft()->assets->isMergeInProgress() && is_object($conflictingRecord)));

		if ($conflict)
		{
			$response = new AssetOperationResponseModel();
			return $response->setPrompt($this->getUserPromptOptions($fileName))->setDataItem('fileName', $fileName);
		}

		if (!IOHelper::move($this->_getFileSystemPath($file), $newServerPath))
		{
			$response = new AssetOperationResponseModel();
			return $response->setError(Craft::t('Could not move the file “{filename}”.', array('filename' => $fileName)));
		}

		if ($file->kind == 'image')
		{
			if ($targetFolder->sourceId == $file->sourceId)
			{
				$transforms = craft()->assetTransforms->getAllCreatedTransformsForFile($file);

				$destination = clone $file;
				$destination->filename = $fileName;

				// Move transforms
				foreach ($transforms as $index)
				{
					// For each file, we have to have both the source and destination
					// for both files and transforms, so we can reliably move them
					$destinationIndex = clone $index;

					if (!empty($index->filename))
					{
						$destinationIndex->filename = $fileName;
						craft()->assetTransforms->storeTransformIndexData($destinationIndex);
					}

					$from = $file->folderPath.craft()->assetTransforms->getTransformSubpath($file, $index);
					$to   = $targetFolder->path.craft()->assetTransforms->getTransformSubpath($destination, $destinationIndex);

					$this->copySourceFile($from, $to);
					$this->deleteSourceFile($from);
				}
			}
			else
			{
				craft()->assetTransforms->deleteAllTransformData($file);
			}
		}

		$response = new AssetOperationResponseModel();

		return $response->setSuccess()
				->setDataItem('newId', $file->id)
				->setDataItem('newFileName', $fileName);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::copySourceFile()
	 *
	 * @param string $sourceUri
	 * @param string $targetUri
	 *
	 * @return bool
	 */
	protected function copySourceFile($sourceUri, $targetUri)
	{
		if ($sourceUri == $targetUri)
		{
			return true;
		}

		return IOHelper::copyFile($this->getSourceFileSystemPath().$sourceUri, $this->getSourceFileSystemPath().$targetUri, true);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::createSourceFolder()
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param string           $folderName
	 *
	 * @return bool
	 */
	protected function createSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		if (!IOHelper::isWritable($this->getSourceFileSystemPath().$parentFolder->path))
		{
			return false;
		}

		return IOHelper::createFolder($this->getSourceFileSystemPath().$parentFolder->path.$folderName);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::renameSourceFolder()
	 *
	 * @param AssetFolderModel $folder
	 * @param string           $newName
	 *
	 * @return bool
	 */
	protected function renameSourceFolder(AssetFolderModel $folder, $newName)
	{
		$newFullPath = IOHelper::getParentFolderPath($folder->path).$newName.'/';

		return IOHelper::rename(
			$this->getSourceFileSystemPath().$folder->path,
			$this->getSourceFileSystemPath().$newFullPath);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::deleteSourceFolder()
	 *
	 * @param AssetFolderModel $parentFolder
	 * @param string           $folderName
	 *
	 * @return bool
	 */
	protected function deleteSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		return IOHelper::deleteFolder($this->getSourceFileSystemPath().$parentFolder->path.$folderName);
	}

	/**
	 * @inheritDoc BaseAssetSourceType::canMoveFileFrom()
	 *
	 * @param BaseAssetSourceType $originalSource
	 *
	 * @return mixed
	 */
	protected function canMoveFileFrom(BaseAssetSourceType $originalSource)
	{
		return $originalSource->isSourceLocal();
	}

	// Private Methods
	// =========================================================================

	/**
	 * Get a file's system path.
	 *
	 * @param AssetFileModel $file
	 *
	 * @return string
	 */
	private function _getFileSystemPath(AssetFileModel $file)
	{
		$fileSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);

		return $this->getSourceFileSystemPath($fileSourceType).$file->getPath();
	}
}
