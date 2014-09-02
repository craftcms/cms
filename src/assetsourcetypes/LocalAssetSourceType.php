<?php
namespace Craft;

/**
 * The local asset source type class. Handles the implementation of the local filesystem as an asset source type in
 * Craft.
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.assetsourcetypes
 * @since      1.0
 * @deprecated This class will most likely be removed in Craft 3.0.
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
	 * Returns the name of the source type.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Local Folder');
	}

	/**
	 * Returns the component's settings HTML.
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
	 * Preps the settings before they're saved to the database.
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
	 * Starts an indexing session.
	 *
	 * @param string $sessionId Indexing session id.
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
	 * Process an indexing session.
	 *
	 * @param string $sessionId Indexing session id.
	 * @param int    $offset    The offset of the item to index.
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
				list ($width, $height) = getimagesize($indexEntryModel->uri);
				$fileModel->width = $width;
				$fileModel->height = $height;
			}

			craft()->assets->storeFile($fileModel);

			return $fileModel->id;
		}

		return false;
	}

	/**
	 * Put an image transform for the File and Transform Index using the
	 * provided path to the source image.
	 *
	 * @param AssetFileModel           $file        The AssetFileModel that the
	 *                                              transform belongs to
	 * @param AssetTransformIndexModel $index       The handle of the transform.
	 * @param string                   $sourceImage The source image.
	 *
	 * @return mixed
	 */
	public function putImageTransform(AssetFileModel $file, AssetTransformIndexModel $index, $sourceImage)
	{
		$folder =  $this->getSourceFileSystemPath().$file->getFolder()->path;
		$targetPath = $folder.craft()->assetTransforms->getTransformSubpath($file, $index);
		return IOHelper::copyFile($sourceImage, $targetPath);
	}

	/**
	 * Get the image source path
	 *
	 * @param AssetFileModel $file The file to get the source path for.
	 *
	 * @return mixed
	 */
	public function getImageSourcePath(AssetFileModel $file)
	{
		return $this->getSourceFileSystemPath().$file->getFolder()->path.$file->filename;
	}

	/**
	 * Make a local copy of the file and return the path to it.
	 *
	 * @param AssetFileModel $file The file to get a local copy of.
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
	 * Return true if a physical folder exists.
	 *
	 * @param AssetFolderModel $parentPath
	 * @param string           $folderName
	 *
	 * @return boolean
	 */
	public function folderExists($parentPath, $folderName)
	{
		return IOHelper::folderExists($this->getSourceFileSystemPath().$parentPath.$folderName);
	}

	/**
	 * Return the source's base URL.
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
	 * Insert a file from path in folder.
	 *
	 * @param AssetFolderModel $folder   The folder to insert the files into.
	 * @param string           $filePath The location of the file to insert.
	 * @param string           $fileName The filename to use.
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
	 * Get a name replacement for a filename already taken in a folder.
	 *
	 * @param AssetFolderModel $folder   The folder to check.
	 * @param string           $fileName The filename to check.
	 *
	 * @return string
	 */
	protected function getNameReplacement(AssetFolderModel $folder, $fileName)
	{
		$fileList = IOHelper::getFolderContents($this->getSourceFileSystemPath().$folder->path, false);
		$existingFiles = array();

		foreach ($fileList as $file)
		{
			$existingFiles[IOHelper::getFileName($file)] = true;
		}

		// Double-check
		if (!isset($existingFiles[$fileName]))
		{
			return $fileName;
		}

		$fileParts = explode(".", $fileName);
		$extension = array_pop($fileParts);
		$fileName = join(".", $fileParts);

		for ($i = 1; $i <= 50; $i++)
		{
			if (!isset($existingFiles[$fileName.'_'.$i.'.'.$extension]))
			{
				return $fileName.'_'.$i.'.'.$extension;
			}
		}

		return false;
	}

	/**
	 * Defines the settings.
	 *
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
	 * Delete just the source file for an Assets File.
	 *
	 * @param string $subpath The subpath of the file to delete within the source.
	 *
	 * @return null
	 */
	protected function deleteSourceFile($subpath)
	{
		IOHelper::deleteFile($this->getSourceFileSystemPath().$subpath, true);
	}

	/**
	 * Move a file in source.
	 *
	 * @param AssetFileModel   $file         The file to move.
	 * @param AssetFolderModel $targetFolder The folder where to move the file.
	 * @param string           $fileName     The filename to use.
	 * @param bool             $overwrite    If true, will overwrite target
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

				// Move transforms
				foreach ($transforms as $index)
				{
					$this->copyTransform($file, $targetFolder, $index, $index);
					$this->deleteSourceFile($file->getFolder()->path.craft()->assetTransforms->getTransformSubpath($file, $index));
				}
			}
			else
			{
				craft()->assetTransforms->deleteCreatedTransformsForFile($file);
			}
		}

		$response = new AssetOperationResponseModel();

		return $response->setSuccess()
				->setDataItem('newId', $file->id)
				->setDataItem('newFileName', $fileName);
	}

	/**
	 * Copy a physical file inside the source.
	 *
	 * @param string $sourceUri The source URI of the file.
	 * @param string $targetUri The target URI of the file.
	 *
	 * @return bool
	 */
	protected function copySourceFile($sourceUri, $targetUri)
	{
		return IOHelper::copyFile($this->getSourceFileSystemPath().$sourceUri, $this->getSourceFileSystemPath().$targetUri, true);
	}

	/**
	 * Create a physical folder, return true on success.
	 *
	 * @param AssetFolderModel $parentFolder The folder in which to create it.
	 * @param string           $folderName   The name of the new folder.
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
	 * Rename a source folder.
	 *
	 * @param AssetFolderModel $folder  The folder to rename.
	 * @param string           $newName The new name.
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
	 * Delete the source folder.
	 *
	 * @param AssetFolderModel $parentFolder The parent folder.
	 * @param string           $folderName   THe folder to delete.
	 *
	 * @return bool
	 */
	protected function deleteSourceFolder(AssetFolderModel $parentFolder, $folderName)
	{
		return IOHelper::deleteFolder($this->getSourceFileSystemPath().$parentFolder->path.$folderName);
	}

	/**
	 * Determines if a file can be moved internally from original source.
	 *
	 * @param BaseAssetSourceType $originalSource The source with whom to test.
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
		$folder = $file->getFolder();
		$fileSourceType = craft()->assetSources->getSourceTypeById($file->sourceId);

		return $this->getSourceFileSystemPath($fileSourceType).$folder->path.$file->filename;
	}
}
