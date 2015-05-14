<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\dates\DateTime;
use craft\app\db\Query;
use craft\app\elements\Asset;
use craft\app\elements\db\AssetQuery;
use craft\app\errors\ActionCancelledException;
use craft\app\errors\AssetConflictException;
use craft\app\errors\AssetDisallowedExtensionException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\AssetMissingException;
use craft\app\errors\EventException;
use craft\app\errors\VolumeException;
use craft\app\errors\VolumeFileExistsException;
use craft\app\errors\VolumeFileNotFoundException;
use craft\app\errors\VolumeFolderExistsException;
use craft\app\errors\ElementSaveException;
use craft\app\errors\Exception;
use craft\app\errors\FileException;
use craft\app\errors\ModelValidationException;
use craft\app\events\AssetEvent;
use craft\app\events\ReplaceAssetEvent;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\DbHelper;
use craft\app\helpers\ImageHelper;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use craft\app\models\AssetTransformIndex;
use craft\app\models\VolumeFolder as VolumeFolderModel;
use craft\app\models\FolderCriteria;
use craft\app\models\VolumeFolder;
use craft\app\records\Asset as AssetRecord;
use craft\app\records\VolumeFolder as VolumeFolderRecord;
use yii\base\Component;

/**
 * Class Assets service.
 *
 * An instance of the Assets service is globally accessible in Craft via [[Application::assets `Craft::$app->getAssets()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Assets extends Component
{
	// Constants
	// =========================================================================

	/**
	 * @event AssetEvent The event that is triggered before an asset is uploaded.
	 *
	 * You may set [[AssetEvent::performAction]] to `false` to prevent the asset from getting saved.
	 */
	const EVENT_BEFORE_UPLOAD_ASSET = 'beforeUploadAsset';

	/**
	 * @event AssetEvent The event that is triggered before an asset is saved.
	 */
	const EVENT_BEFORE_SAVE_ASSET = 'beforeSaveAsset';

	/**
	 * @event AssetEvent The event that is triggered after an asset is saved.
	 */
	const EVENT_AFTER_SAVE_ASSET = 'afterSaveAsset';

	/**
	 * @event AssetEvent The event that is triggered before an asset is replaced.
	 */
	const EVENT_BEFORE_REPLACE_FILE = 'beforeReplaceFile';

	/**
	 * @event AssetEvent The event that is triggered after an asset is replaced.
	 */
	const EVENT_AFTER_REPLACE_FILE = 'afterReplaceFile';

	/**
	 * @event AssetEvent The event that is triggered before an asset is deleted.
	 */
	const EVENT_BEFORE_DELETE_ASSET = 'beforeDeleteAsset';

	/**
	 * @event AssetEvent The event that is triggered after an asset is deleted.
	 */
	const EVENT_AFTER_DELETE_ASSET = 'afterDeleteAsset';

	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_foldersById;

	// Public Methods
	// =========================================================================

	/**
	 * Returns all top-level files in a volume.
	 *
	 * @param int         $volumeId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getFilesByVolumeId($volumeId, $indexBy = null)
	{
		return Asset::find()
			->volumeId($volumeId)
			->indexBy($indexBy)
			->all();
	}

	/**
	 * Returns a file by its ID.
	 *
	 * @param             $fileId
	 * @param string|null $localeId
	 *
	 * @return Asset|null
	 */
	public function getFileById($fileId, $localeId = null)
	{
		return Craft::$app->getElements()->getElementById($fileId, Asset::className(), $localeId);
	}

	/**
	 * Finds the first file that matches the given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return Asset|null
	 */
	public function findFile($criteria = null)
	{
		$query = $this->_createFileQuery($criteria);

		return $query->one();
	}

	/**
	 * Finds all files that matches the given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return array|null
	 */
	public function findFiles($criteria = null)
	{
		$query = $this->_createFileQuery($criteria);

		return $query->all();
	}

	/**
	 * Gets the total number of files that match a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return int
	 */
	public function getTotalFiles($criteria = null)
	{
		if ($criteria instanceof AssetQuery)
		{
			$query = $criteria;
		}
		else
		{
			$query = Asset::find()->configure($criteria);
		}

		return $query->count();
	}

	/**
	 * Save an Asset.
	 *
	 * Saves an Asset. If the 'newFilePath' property is set, will replace the existing file.
	 * For new files, this property MUST bet set.
	 *
	 * @param Asset $asset
	 *
	 * @throws AssetDisallowedExtensionException If the file extension is not allowed.
	 * @throws FileException                     If there was a problem with the actual file.
	 * @throws AssetConflictException            If a file with such name already exists.
	 * @throws AssetLogicException               If something violates Asset's logic (e.g. Asset outside of a folder).
	 * @throws VolumeFileExistsException         If the file actually exists on the volume, but on in the index.
	 * @return void
	 */
	public function saveAsset(Asset $asset)
	{
		$isNew = empty($asset->id);

		if ($isNew && empty($asset->newFilePath) && empty($asset->indexInProgress))
		{
			throw new AssetLogicException(Craft::t('app', 'A new Asset cannot be created without a file.'));
		}

		if (empty($asset->folderId))
		{
			throw new AssetLogicException(Craft::t('app', 'All Assets must have folder ID set.'));
		}

		$extension = $asset->getExtension();

		if (!IOHelper::isExtensionAllowed($extension))
		{
			throw new AssetDisallowedExtensionException(Craft::t('app', 'The extension “{extension}” is not allowed.', array('extension' => $extension)));
		}

		$asset->filename = AssetsHelper::prepareAssetName($asset->filename);

		$existingAsset = $this->findFile(array('filename' => $asset->filename, 'folderId' => $asset->folderId));

		if ($existingAsset && $existingAsset->id != $asset->id)
		{
			throw new AssetConflictException(Craft::t('app', 'A file with the name “{filename}” already exists in the folder.', array('filename' => $asset->filename)));
		}

		$volume = $asset->getVolume();

		if (!$volume)
		{
			throw new AssetLogicException(Craft::t('app', 'Volume does not exist with the id of {id}.', array('id' => $asset->volumeId)));
		}

		if (!empty($asset->newFilePath))
		{
			$stream = fopen($asset->newFilePath, 'r');

			if (!$stream)
			{
				throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', array('path' => $asset->newFilePath)));
			}

			$uriPath = $asset->getUri();

			$event = new AssetEvent(['asset' => $asset]);
			$this->trigger(static::EVENT_BEFORE_UPLOAD_ASSET, $event);

			// Explicitly re-throw VolumeFileExistsException
			try
			{
				$volume->createFileByStream($uriPath, $stream);
			}
			catch (VolumeFileExistsException $exception)
			{
				throw $exception;
			}

			if (is_resource($stream))
			{
				fclose($stream);
			}

			$asset->dateModified = new DateTime();
			$asset->size = IOHelper::getFileSize($asset->newFilePath);
			$asset->kind = IOHelper::getFileKind($asset->getExtension());

			if ($asset->kind == 'image' && !empty($asset->newFilePath))
			{
				list ($asset->width, $asset->height) = getimagesize($asset->newFilePath);
			}
		}

		$this->_storeAssetRecord($asset);

		// Now that we have an ID, store the source
		if (!$volume->isLocal() && $asset->kind == 'image' && !empty($asset->newFilePath))
		{
			// Store the local source for now and set it up for deleting, if needed
			Craft::$app->getAssetTransforms()->storeLocalSource($asset->newFilePath, $asset->getImageTransformSourcePath());
			Craft::$app->getAssetTransforms()->queueSourceForDeletingIfNecessary($asset->getImageTransformSourcePath());
		}
	}

	/**
	 * Replaces an Asset with another.
	 *
	 * @param Asset $fileToReplace
	 * @param Asset $fileToReplaceWith
	 * @param bool  $mergeAssets whether to replace content as well.
	 *
	 * @return null
	 */
	public function replaceAsset(Asset $fileToReplace, Asset $fileToReplaceWith, $mergeAssets = false)
	{
		$targetVolume = $fileToReplace->getVolume();

		// TODO purge cached files for remote Volumes.

		// Clear all thumb and transform data
		if (ImageHelper::isImageManipulatable($fileToReplace->getExtension()))
		{
			Craft::$app->getAssetTransforms()->deleteAllTransformData($fileToReplace);
		}

		// Handle things differently depening on whether that's an upload or a file move.
		if ($mergeAssets)
		{
			Craft::$app->getElements()->mergeElementsByIds($fileToReplace->id, $fileToReplaceWith->id);

			// Replace the file - delete the conflicting file and move the file in it's place.
			$targetVolume->deleteFile($fileToReplace->getUri());
			$this->_moveFileToFolder($fileToReplaceWith, $fileToReplace->getFolder());

			$fileToReplaceWith->folderId = $fileToReplace->folderId;
			$fileToReplaceWith->volumeId = $fileToReplace->volumeId;
			$fileToReplaceWith->filename = $fileToReplace->filename;
			$this->saveAsset($fileToReplaceWith);
		}
		else
		{
			// Update the attributes and save the Asset
			$fileToReplace->dateModified = $fileToReplaceWith->dateModified;
			$fileToReplace->size         = $fileToReplaceWith->size;
			$fileToReplace->kind         = $fileToReplaceWith->kind;
			$fileToReplace->width        = $fileToReplaceWith->width;
			$fileToReplace->height       = $fileToReplaceWith->height;

			// Replace the file - delete the conflicting file and move the file in it's place.
			$targetVolume->deleteFile($fileToReplace->getUri());
			$this->_moveFileToFolder($fileToReplaceWith, $fileToReplace->getFolder(), $fileToReplace->filename);

			$this->saveAsset($fileToReplace);
			$this->deleteFilesByIds($fileToReplaceWith->id, false);
		}

	}

	/**
	 * Replace an Asset's file.
	 *
	 * Replace an Asset's file by it's id, a local file and the filename to use.
	 *
	 * @param $assetId
	 * @param $pathOnServer
	 * @param $filename
	 *g
	 * @throws EventException
	 * @throws FileException
	 * @throws AssetLogicException
	 * @return void
	 */
	public function replaceAssetFile($assetId, $pathOnServer, $filename)
	{
		$existingFile = $this->getFileById($assetId);

		if (!$existingFile)
		{
			throw new AssetLogicException(Craft::t('app', 'The asset to be replaced cannot be found.'));
		}

		$event = new ReplaceAssetEvent(['asset' => $existingFile, 'replaceWith' => $pathOnServer, 'filename' => $filename]);
		$this->trigger(static::EVENT_BEFORE_REPLACE_FILE, $event);

		// Is the event preventing this from happening?
		if (!$event->performAction)
		{
			throw new EventException(Craft::t('app', 'Something prevented the Asset file from being replaced.'));
		}

		// TODO check event

		$existingFile = $this->getFileById($assetId);

		$volume = $existingFile->getVolume();

		// Clear all thumb and transform data
		if (ImageHelper::isImageManipulatable($existingFile->getExtension()))
		{
			Craft::$app->getAssetTransforms()->deleteAllTransformData($existingFile);
		}

		// Open the stream for, uhh, streaming
		$stream = fopen($pathOnServer, 'r');

		if (!$stream)
		{
			throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', array('path' => $pathOnServer)));
		}

		// Re-use the same filename
		if (StringHelper::toLowerCase($existingFile->filename) == StringHelper::toLowerCase($filename))
		{
			// The case is changing in the filename
			if ($existingFile->filename != $filename)
			{
				// Delete old, change the name, upload the new
				$volume->deleteFile($existingFile->getUri());
				$existingFile->filename = $filename;
				$volume->createFileByStream($existingFile->getUri(), $stream);
			}
			else
			{
				$volume->updateFileByStream($existingFile->getUri(), $stream);
			}
		}
		else
		{
			// Get an available name to avoid conflicts and upload the file
			$filename = $this->getNameReplacementInFolder($filename, $existingFile->getFolder());

			// Delete old, change the name, upload the new
			$volume->deleteFile($existingFile->getUri());
			$existingFile->filename = $filename;
			$volume->createFileByStream($existingFile->getUri(), $stream);

			$existingFile->kind = IOHelper::getFileKind(IOHelper::getExtension($filename));
		}

		if (is_resource($stream))
		{
			fclose($stream);
		}

		if ($existingFile->kind == "image")
		{
			list ($existingFile->width, $existingFile->height) = getimagesize($pathOnServer);
		}
		else
		{
			$existingFile->width = null;
			$existingFile->height = null;
		}

		$existingFile->size = IOHelper::getFileSize($pathOnServer);
		$existingFile->dateModified = IOHelper::getLastTimeModified($pathOnServer);

		$this->saveAsset($existingFile);

		$event = new ReplaceAssetEvent(['asset' => $existingFile, 'filename' => $filename]);
		$this->trigger(static::EVENT_AFTER_REPLACE_FILE, $event);
	}

	/**
	 * Delete a list of files by an array of ids (or a single id).
	 *
	 * @param array|int $fileIds
	 * @param bool      $deleteFile Should the file be deleted along the record. Defaults to true.
	 *
	 * @return null
	 */
	public function deleteFilesByIds($fileIds, $deleteFile = true)
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		foreach ($fileIds as $fileId)
		{
			$file = $this->getFileById($fileId);

			if ($file)
			{
				$volume = $file->getVolume();

				// Fire an 'onBeforeDeleteAsset' event
				$event = new AssetEvent($this, array(
					'asset' => $file
				));
				$this->trigger(static::EVENT_BEFORE_DELETE_ASSET, $event);

				if ($event->performAction)
				{
					if ($deleteFile)
					{
						$volume->deleteFile($file->getUri());
					}

					Craft::$app->getElements()->deleteElementById($fileId);

					$this->trigger(static::EVENT_AFTER_DELETE_ASSET, $event);
				}
			}
		}
	}

	/**
	 * Rename an Asset.
	 *
	 * @param Asset  $asset
	 * @param string $newFilename
	 *
	 * @throws AssetDisallowedExtensionException If the extension is not allowed.
	 * @throws AssetConflictException            If a file with such a name already exists/
	 * @throws AssetLogicException               If something violates Asset's logic (e.g. Asset outside of a folder).
	 * @return null
	 */
	public function renameAsset(Asset $asset, $newFilename)
	{
		$extension = IOHelper::getExtension($newFilename);

		if (!IOHelper::isExtensionAllowed($extension))
		{
			throw new AssetDisallowedExtensionException(Craft::t('app', 'The extension “{extension}” is not allowed.', array('extension' => $extension)));
		}

		$newFilename = AssetsHelper::prepareAssetName($newFilename);

		$existingAsset = $this->findFile(array('filename' => $newFilename, 'folderId' => $asset->folderId));

		if ($existingAsset && $existingAsset->id != $asset->id)
		{
			throw new AssetConflictException(Craft::t('app', 'A file with the name “{filename}” already exists in the folder.', array('filename' => $newFilename)));
		}

		$volume = $asset->getVolume();

		if (!$volume)
		{
			throw new AssetLogicException(Craft::t('app', 'Volume does not exist with the id of {id}.', array('id' => $asset->volumeId)));
		}

		if ($volume->renameFile($asset->getUri(), $asset->getUri($newFilename)))
		{
			$asset->filename = $newFilename;
			$this->_storeAssetRecord($asset);
		}
	}

	/**
	 * Save an Asset folder.
	 *
	 * @param VolumeFolderModel $folder
	 *
	 * @throws AssetConflictException           If a folder already exists with such a name.
	 * @throws AssetLogicException              If something violates Asset's logic (e.g. Asset outside of a folder).
	 * @throws VolumeFolderExistsException      If the file actually exists on the volume, but on in the index.
	 * @return void
	 */
	public function createFolder(VolumeFolderModel $folder)
	{
		$parent = $folder->getParent();

		if (!$parent)
		{
			throw new AssetLogicException(Craft::t('app', 'No folder exists with the ID “{id}”', array('id' => $folder->parentId)));
		}

		$existingFolder = $this->findFolder(array('parentId' => $folder->parentId, 'name' => $folder->name));

		if ($existingFolder && (empty($folder->id) || $folder->id != $existingFolder))
		{
			throw new AssetConflictException(Craft::t('app', 'A folder with the name “{folderName}” already exists in the folder.', array('folderName' => $folder->name)));
		}

		$volume = $parent->getVolume();

		// Explicitly re-throw VolumeFolderExistsException
		try
		{
			$volume->createDir(rtrim($folder->path, '/'));
		}
		catch (VolumeFolderExistsException $exception)
		{
			throw $exception;
		}
		$this->storeFolderRecord($folder);
	}

	/**
	 * Deletes a folder by its ID.
	 *
	 * @param array|int $folderIds
 	 * @param bool      $deleteFolder Should the file be deleted along the record. Defaults to true.
	 *
	 * @throws VolumeException If deleting a single folder and it cannot be deleted.
	 * @return null
	 */
	public function deleteFoldersByIds($folderIds, $deleteFolder = true)
	{
		if (!is_array($folderIds))
		{
			$folderIds = array($folderIds);
		}

		foreach ($folderIds as $folderId)
		{
			$folder = $this->getFolderById($folderId);

			if ($folder)
			{
				if ($deleteFolder)
				{
					$volume = $folder->getVolume();

					// If this is a batch operation, don't stop the show
					if (!$volume->deleteDir($folder->path) && count($folderIds) == 1)
					{
						throw new VolumeException(Craft::t('app', 'Folder “{folder}” cannot be deleted!', array('folder' => $folder->path)));
					}
				}

				VolumeFolderRecord::deleteAll(['id' => $folderId]);
			}
		}
	}

	/**
	 * Get the folder tree for Assets by volume ids
	 *
	 * @param $allowedVolumeIds
	 *
	 * @return array
	 */
	public function getFolderTreeByVolumeIds($allowedVolumeIds)
	{
		$folders = $this->findFolders(array('volumeId' => $allowedVolumeIds, 'order' => 'path'));
		$tree = $this->_getFolderTreeByFolders($folders);

		$sort = array();

		foreach ($tree as $topFolder)
		{
			/**
			 * @var VolumeFolderModel $topFolder;
			 */
			$sort[] = $topFolder->getVolume()->sortOrder;
		}

		array_multisort($sort, $tree);

		return $tree;
	}

	/**
	 * Get the folder tree for Assets by a folder id.
	 *
	 * @param $folderId
	 *
	 * @return array
	 */
	public function getFolderTreeByFolderId($folderId)
	{
		$folder = $this->getFolderById($folderId);

		if (is_null($folder))
		{
			return array();
		}

		return $this->_getFolderTreeByFolders(array($folder));
	}

	/**
	 * Returns a folder by its ID.
	 *
	 * @param int $folderId
	 *
	 * @return VolumeFolderModel|null
	 */
	public function getFolderById($folderId)
	{
		if (!isset($this->_foldersById) || !array_key_exists($folderId, $this->_foldersById))
		{
			$result = $this->_createFolderQuery()
				->where('id = :id', array(':id' => $folderId))
				->one();

			if ($result)
			{
				$folder = new VolumeFolderModel($result);
			}
			else
			{
				$folder = null;
			}

			$this->_foldersById[$folderId] = $folder;
		}

		return $this->_foldersById[$folderId];
	}

	/**
	 * Finds folders that match a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return array
	 */
	public function findFolders($criteria = null)
	{
		if (!($criteria instanceof FolderCriteria))
		{
			$criteria = new FolderCriteria($criteria);
		}

		$query = (new Query())
			->select('f.*')
			->from('{{%volumefolders}} AS f');

		$this->_applyFolderConditions($query, $criteria);

		if ($criteria->order)
		{
			$query->orderBy($criteria->order);
		}

		if ($criteria->offset)
		{
			$query->offset($criteria->offset);
		}

		if ($criteria->limit)
		{
			$query->limit($criteria->limit);
		}

		$results = $query->all();
		$folders = array();

		foreach ($results as $result)
		{
			$folder = VolumeFolderModel::create($result);
			$this->_foldersById[$folder->id] = $folder;
			$folders[] = $folder;
		}

		return $folders;
	}

	/**
	 * Returns all of the folders that are descendants of a given folder.
	 *
	 * @param VolumeFolderModel $parentFolder
	 * @param string $orderBy
	 *
	 * @return array
	 */
	public function getAllDescendantFolders(VolumeFolderModel $parentFolder, $orderBy = "path")
	{
		/**
		 * @var $query Query
		 */
		$query = (new Query())
			->select('f.*')
			->from('{{%volumefolders}} AS f')
			->where(['like', 'path', $parentFolder->path.'%', false])
			->andWhere('volumeId = :volumeId', array(':volumeId' => $parentFolder->volumeId))
			->andWhere('parentId IS NOT NULL');

		if ($orderBy)
		{
			$query->orderBy($orderBy);
		}

		$results = $query->all();
		$descendantFolders = array();

		foreach ($results as $result)
		{
			$folder = VolumeFolderModel::create($result);
			$this->_foldersById[$folder->id] = $folder;
			$descendantFolders[$folder->id] = $folder;
		}

		return $descendantFolders;
	}

	/**
	 * Finds the first folder that matches a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return VolumeFolderModel|null
	 */
	public function findFolder($criteria = null)
	{
		if (!($criteria instanceof FolderCriteria))
		{
			$criteria = new FolderCriteria($criteria);
		}

		$criteria->limit = 1;
		$folder = $this->findFolders($criteria);

		if (is_array($folder) && !empty($folder))
		{
			return array_pop($folder);
		}

		return null;
	}

	/**
	 * Gets the total number of folders that match a given criteria.
	 *
	 * @param mixed $criteria
	 *
	 * @return int
	 */
	public function getTotalFolders($criteria)
	{
		if (!($criteria instanceof FolderCriteria))
		{
			$criteria = new FolderCriteria($criteria);
		}

		$query = (new Query())
			->select('count(id)')
			->from('{{%volumefolders}} AS f');

		$this->_applyFolderConditions($query, $criteria);

		return (int) $query->scalar();
	}

	// File and folder managing
	// -------------------------------------------------------------------------


	/**
	 * Get URL for a file.
	 *
	 * @param Asset  $file
	 * @param string $transform
	 *
	 * @return string
	 */
	public function getUrlForFile(Asset $file, $transform = null)
	{
		//TODO Asset thumb cache bust?
		if (!$transform || !ImageHelper::isImageManipulatable(IOHelper::getExtension($file->filename)))
		{
			$volume = $file->getVolume();

			return AssetsHelper::generateUrl($volume, $file);
		}

		// Get the transform index model
		$index = Craft::$app->getAssetTransforms()->getTransformIndex($file, $transform);

		// Does the file actually exist?
		if ($index->fileExists)
		{
			return Craft::$app->getAssetTransforms()->getUrlForTransformByTransformIndex($index);
		}
		else
		{
			if (Craft::$app->getConfig()->get('generateTransformsBeforePageLoad'))
			{
				// Mark the transform as in progress
				$index->inProgress = true;
				Craft::$app->getAssetTransforms()->storeTransformIndexData($index);

				// Generate the transform
				Craft::$app->getAssetTransforms()->generateTransform($index);

				// Update the index
				$index->fileExists = true;
				Craft::$app->getAssetTransforms()->storeTransformIndexData($index);

				// Return the transform URL
				return Craft::$app->getAssetTransforms()->getUrlForTransformByTransformIndex($index);
			}
			else
			{
				// Queue up a new Generate Pending Transforms task, if there isn't one already
				if (!Craft::$app->getTasks()->areTasksPending('GeneratePendingTransforms'))
				{
					Craft::$app->getTasks()->createTask('GeneratePendingTransforms');
				}

				// Return the temporary transform URL
				return UrlHelper::getResourceUrl('transforms/'.$index->id);
			}
		}
	}

	/**
	 * Find a replacement for a filename
	 *
	 * @param string       $filename
	 * @param VolumeFolderModel $folder
	 * @throws AssetLogicException
	 * @return string
	 */
	public function getNameReplacementInFolder($filename, VolumeFolderModel $folder)
	{
		$volume = $folder->getVolume();
		$fileList = $volume->getFileList($folder->path);

		// Flip the array for faster lookup
		$existingFiles = [];

		foreach ($fileList as $file)
		{
			if (StringHelper::toLowerCase(rtrim($folder->path, '/')) == StringHelper::toLowerCase($file['dirname']))
			{
				$existingFiles[StringHelper::toLowerCase($file['basename'])] = true;
			}
		}

		// See if we can use the original filename
		if (!isset($existingFiles[StringHelper::toLowerCase($filename)]))
		{
			return $filename;
		}


		$filenameParts = explode(".", $filename);
		$extension = array_pop($filenameParts);

		for ($i = 1; $i <= 50; $i++)
		{
			$proposedFilename = join(".", $filenameParts).'_'.$i.'.'.$extension;
			if (!isset($existingFiles[StringHelper::toLowerCase($proposedFilename)]))
			{
				return $proposedFilename;
			}
		}

		throw new AssetLogicException(Craft::t('app', 'Could not find a suitable replacement filename for “{filename}”.', array('filename' => $filename)));
	}

	/**
	 * Move an Asset.
	 *
	 * @param Asset $asset
	 * @param int $folderId Id of the folder of the destination
	 * @param string $newFilename filename to use for the file at it's destination
	 *
	 * @throws AssetDisallowedExtensionException If the extension is not allowed.
	 * @throws AssetConflictException            If there is a conflict.
	 * @throws AssetLogicException               If the target folder does not exist.
	 * @return null
	 */
	public function moveAsset(Asset $asset, $folderId, $newFilename = "")
	{
		$filename = $newFilename ?: $asset->filename;

		$extension = IOHelper::getExtension($filename);

		if (!IOHelper::isExtensionAllowed($extension))
		{
			throw new AssetDisallowedExtensionException(Craft::t('app', 'The extension “{extension}” is not allowed.', array('extension' => $extension)));
		}

		$existingAsset = $this->findFile(array('filename' => $filename, 'folderId' => $folderId));

		if ($existingAsset && $existingAsset->id != $asset->id)
		{
			throw new AssetConflictException(Craft::t('app', 'A file with the name “{filename}” already exists in the folder.', array('filename' => $filename)));
		}

		$targetFolder = $this->getFolderById($folderId);

		if (!$targetFolder)
		{
			throw new AssetLogicException(Craft::t('app', 'The destination folder does not exist'));
		}

		$this->_moveFileToFolder($asset, $targetFolder, $filename);

		$asset->folderId = $folderId;
		$asset->volumeId = $targetFolder->volumeId;
		$asset->filename = $filename;

		$this->saveAsset($asset);
	}


	/**
	 * Return true if user has permission to perform the action on the folder.
	 *
	 * @param $folderId
	 * @param $action
	 *
	 * @return bool
	 */
	public function canUserPerformAction($folderId, $action)
	{
		try
		{
			$this->checkPermissionByFolderIds($folderId, $action);
			return true;
		}
		catch (Exception $exception)
		{
			return false;
		}
	}

	/**
	 * Check for a permission on a volumeId by a folder id or an array of folder ids.
	 *
	 * @param $folderIds
	 * @param $permission
	 *
	 * @throws Exception
	 * @return null
	 */
	public function checkPermissionByFolderIds($folderIds, $permission)
	{
		if (!is_array($folderIds))
		{
			$folderIds = array($folderIds);
		}

		foreach ($folderIds as $folderId)
		{
			$folderModel = $this->getFolderById($folderId);

			if (!$folderModel)
			{
				throw new Exception(Craft::t('app', 'That folder does not seem to exist anymore. Re-index the Assets volume and try again.'));
			}

			if (!Craft::$app->user->checkPermission($permission.':'.$folderModel->volumeId))
			{
				throw new Exception(Craft::t('app', 'You don’t have the required permissions for this operation.'));
			}
		}
	}

	/**
	 * Check for a permission on a volume by a file id or an array of file ids.
	 *
	 * @param $fileIds
	 * @param $permission
	 *
	 * @throws Exception
	 * @return null
	 */
	public function checkPermissionByFileIds($fileIds, $permission)
	{
		if (!is_array($fileIds))
		{
			$fileIds = array($fileIds);
		}

		foreach ($fileIds as $fileId)
		{
			$file = $this->getFileById($fileId);

			if (!$file)
			{
				throw new Exception(Craft::t('app', 'That file does not seem to exist anymore. Re-index the Assets volume and try again.'));
			}

			if (!Craft::$app->user->checkPermission($permission.':'.$file->volumeId))
			{
				throw new Exception(Craft::t('app', 'You don’t have the required permissions for this operation.'));
			}
		}
	}

	/**
	 * Ensure a folder entry exists in the DB for the full path and return it's id.
	 *
	 * @param string $fullPath The path to ensure the folder exists at.
	 *
	 * @return int
	 */
	public function ensureFolderByFullPathAndVolumeId($fullPath, $volumeId)
	{
		$parameters = new FolderCriteria(array(
			'path' => $fullPath,
			'volumeId' => $volumeId
		));

		$folderModel = $this->findFolder($parameters);

		// If we don't have a folder matching these, create a new one
		if (is_null($folderModel))
		{
			$parts = explode('/', rtrim($fullPath, '/'));
			$folderName = array_pop($parts);

			if (empty($parts))
			{
				// Looking for a top level folder, apparently.
				$parameters->path = '';
				$parameters->parentId = ':empty:';
			}
			else
			{
				$parameters->path = join('/', $parts).'/';
			}

			// Look up the parent folder
			$parentFolder = $this->findFolder($parameters);

			if (is_null($parentFolder))
			{
				$parentId = ':empty:';
			}
			else
			{
				$parentId = $parentFolder->id;
			}

			$folderModel = new VolumeFolderModel();
			$folderModel->volumeId = $volumeId;
			$folderModel->parentId = $parentId;
			$folderModel->name = $folderName;
			$folderModel->path = $fullPath;

			$this->storeFolderRecord($folderModel);
		}

		return $folderModel->id;
	}

	/**
	 * Store a folder by model
	 *
	 * @param VolumeFolderModel $folder
	 *
	 * @return bool
	 */
	public function storeFolderRecord(VolumeFolderModel $folder)
	{
		if (empty($folder->id))
		{
			$record = new VolumeFolderRecord();
		}
		else
		{
			$record = VolumeFolderRecord::findOne(['id' => $folder->id]);
		}

		$record->parentId = $folder->parentId;
		$record->volumeId = $folder->volumeId;
		$record->name = $folder->name;
		$record->path = $folder->path;
		$record->save();

		$folder->id = $record->id;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Move an Asset's file to the specified folder.
	 *
	 * @param Asset $asset
	 * @param VolumeFolderModel $targetFolder
	 * @param string $newFilename new filename to use
	 *
	 * @throws FileException
	 * @return null
	 */
	private function _moveFileToFolder(Asset $asset, VolumeFolderModel $targetFolder, $newFilename = "")
	{
		$filename = $newFilename ?: $asset->filename;

		$sourceVolume = $asset->getVolume();
		$fromPath = $asset->getUri();
		$toPath = $targetFolder->path.$filename;

		// Move inside the source.
		if ($asset->volumeId == $targetFolder->volumeId)
		{
			$sourceVolume->renameFile($fromPath, $toPath);
			$transformIndexes = Craft::$app->getAssetTransforms()->getAllCreatedTransformsForFile($asset);

			// Move the transforms
			foreach ($transformIndexes as $transformIndex)
			{
				/**
				 * @var AssetTransformIndex $transformIndex
				 */
				$fromTransformPath = Craft::$app->getAssetTransforms()->getTransformSubpath($asset, $transformIndex);
				$toTransformPath = $fromTransformPath;

				// In case we're changing the filename, make sure that we're not missing that.
				$parts = explode("/", $toTransformPath);
				$transformName = array_pop($parts);
				$toTransformPath = join("/", $parts).'/'.IOHelper::getFilename($filename, false).'.'.IOHelper::getExtension($transformName);

				$baseFrom = $asset->getFolder()->path;
				$baseTo = $targetFolder->path;

				// Overwrite existing transforms
				$sourceVolume->deleteFile($baseTo.$toTransformPath);

				try
				{
					$sourceVolume->renameFile($baseFrom.$fromTransformPath, $baseTo.$toTransformPath);
					$transformIndex->filename = $filename;
					Craft::$app->getAssetTransforms()->storeTransformIndexData($transformIndex);
				}
				catch (VolumeFileNotFoundException $exception)
				{
					// No biggie, just delete the transform index as well then
					Craft::$app->getAssetTransforms()->deleteTransformIndex($transformIndex->id);
				}
			}
		}
		// Move between sources
		else
		{
			$localPath = IOHelper::getTempFilePath($asset->getExtension());
			$sourceVolume->saveFileLocally($asset->getUri(), $localPath);
			$targetVolume = $targetFolder->getVolume();
			$stream = fopen($localPath, 'r');

			if (!$stream)
			{
				throw new FileException(Craft::t('app', 'Could not open file for streaming at {path}', array('path' => $asset->newFilePath)));
			}

			$targetVolume->createFileByStream($toPath, $stream);
			$sourceVolume->deleteFile($asset->getUri());

			if (is_resource($stream))
			{
				fclose($stream);
			}

			// Nuke the transforms
			Craft::$app->getAssetTransforms()->deleteAllTransformData($asset);
		}
	}

	/**
	 * Returns an AssetQuery object prepped for retrieving assets.
	 *
	 * @return AssetQuery
	 */
	private function _createFileQuery($criteria)
	{
		if ($criteria instanceof AssetQuery)
		{
			$query = $criteria;
		}
		else
		{
			$query = Asset::find()->configure($criteria);
		}

		if (is_string($query->filename))
		{
			// Backslash-escape any commas in a given string.
			$query->filename = DbHelper::escapeParam($query->filename);
		}

		return $query;
	}

	/**
	 * Returns a DbCommand object prepped for retrieving assets.
	 *
	 * @return Query
	 */
	private function _createFolderQuery()
	{
		return (new Query())
			->select('id, parentId, volumeId, name, path')
			->from('{{%volumefolders}}');
	}

	/**
	 * Return the folder tree form a list of folders.
	 *
	 * @param $folders
	 *
	 * @return array
	 */
	private function _getFolderTreeByFolders($folders)
	{
		$tree = array();
		$referenceStore = array();

		foreach ($folders as $folder)
		{
			/**
			 * @var VolumeFolder $folder
			 */
			if ($folder->parentId && isset($referenceStore[$folder->parentId]))
			{
				$referenceStore[$folder->parentId]->addChild($folder);
			}
			else
			{
				$tree[] = $folder;
			}

			$referenceStore[$folder->id] = $folder;
		}

		$sort = array();

		foreach ($tree as $topFolder)
		{
			/**
			 * @var VolumeFolder $topFolder
			 */
			$sort[] = $topFolder->getVolume()->sortOrder;
		}

		array_multisort($sort, $tree);

		return $tree;
	}

	/**
	 * Applies WHERE conditions to a DbCommand query for folders.
	 *
	 * @param Query          $query
	 * @param FolderCriteria $criteria
	 *
	 * @return null
	 */
	private function _applyFolderConditions($query, FolderCriteria $criteria)
	{
		$whereConditions = array();
		$whereParams = array();

		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('f.id', $criteria->id, $whereParams);
		}

		if ($criteria->volumeId)
		{
			$whereConditions[] = DbHelper::parseParam('f.volumeId', $criteria->volumeId, $whereParams);
		}

		if ($criteria->parentId)
		{
			$whereConditions[] = DbHelper::parseParam('f.parentId', $criteria->parentId, $whereParams);
		}

		if ($criteria->name)
		{
			$whereConditions[] = DbHelper::parseParam('f.name', $criteria->name, $whereParams);
		}

		if (!is_null($criteria->path))
		{
			// This folder has a comma in it.
			if (strpos($criteria->path, ',') !== false)
			{
				// Escape the comma.
				$condition = DbHelper::parseParam('f.path', str_replace(',', '\,', $criteria->path), $whereParams);
				$lastKey = key(array_slice($whereParams, -1, 1, true));

				// Now un-escape it.
				$whereParams[$lastKey] = str_replace('\,', ',', $whereParams[$lastKey]);
			}
			else
			{
				$condition = DbHelper::parseParam('f.path', $criteria->path, $whereParams);
			}

			$whereConditions[] = $condition;
		}

		if (count($whereConditions) == 1)
		{
			$query->where($whereConditions[0], $whereParams);
		}
		else
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions, $whereParams);
		}
	}

	/**
	 * Saves the record for an asset.
	 *
	 * @param Asset $asset
	 *
	 * @throws \Exception
	 * @return bool
	 */
	private function _storeAssetRecord(Asset $asset)
	{
		$isNewAsset = !$asset->id;

		if (!$isNewAsset)
		{
			$assetRecord = AssetRecord::findOne(['id' => $asset->id]);

			if (!$assetRecord)
			{
				throw new AssetMissingException(Craft::t('app', 'No asset exists with the ID “{id}”.', array('id' => $asset->id)));
			}
		}
		else
		{
			$assetRecord = new AssetRecord();
		}

		$assetRecord->volumeId     = $asset->volumeId;
		$assetRecord->folderId     = $asset->folderId;
		$assetRecord->filename     = $asset->filename;
		$assetRecord->kind         = $asset->kind;
		$assetRecord->size         = $asset->size;
		$assetRecord->width        = $asset->width;
		$assetRecord->height       = $asset->height;
		$assetRecord->dateModified = $asset->dateModified;

		$assetRecord->validate();
		$asset->addErrors($assetRecord->getErrors());

		if ($asset->hasErrors())
		{
			$exception = new ModelValidationException(
				Craft::t('app', 'Saving the Asset failed with the following errors: {errors}',
					['errors' => join(', ', $asset->getAllErrors())]
				)
			);

			$exception->setModel($asset);

			throw $exception;
		}

		if ($isNewAsset && !$asset->getContent()->title)
		{
			// Give it a default title based on the file name
			$asset->getContent()->title = str_replace('_', ' ', IOHelper::getFilename($asset->filename, false));
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			$event = new AssetEvent(array(
					'asset' => $asset
				)
			);
			$this->trigger(static::EVENT_BEFORE_SAVE_ASSET, $event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element
				$success = Craft::$app->getElements()->saveElement($asset, false);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveAsset
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					throw new ElementSaveException(Craft::t('app', 'Failed to save the Asset Element'));
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewAsset)
				{
					$assetRecord->id = $asset->id;
				}

				// Save the file row
				$assetRecord->save(false);

				$this->trigger(static::EVENT_AFTER_SAVE_ASSET, $event);
			}
			else
			{
				throw new ActionCancelledException(Craft::t('app', 'A plugin cancelled the save operation for {asset}!', array('asset' => $asset->filename)));
			}

			// Commit the transaction regardless of whether we saved the asset, in case something changed
			// in onBeforeSaveAsset
			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}
}
