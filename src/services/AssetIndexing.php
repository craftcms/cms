<?php
namespace craft\app\services;

use Craft;
use craft\app\assetsourcetypes\BaseAssetSourceType;
use craft\app\dates\DateTime;
use craft\app\helpers\StringHelper;
use craft\app\helpers\AssetsHelper;
use craft\app\helpers\IOHelper;
use craft\app\models\AssetSource;
use craft\app\models\Asset;
use craft\app\models\AssetIndexData as AssetIndexDataModel;
use craft\app\records\AssetFolder;
use craft\app\records\AssetIndexData as AssetIndexDataRecord;
use craft\app\db\Query;
use \yii\base\Component;

/**
 * Class AssetIndexingService
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://buildwithcraft.com/license Craft License Agreement
 * @see        http://buildwithcraft.com
 * @package    craft.app.services
 * @since      1.0
 * @deprecated This class will have several breaking changes in Craft 3.0.
 */
class AssetIndexing extends Component
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns a unique indexing session id.
	 *
	 * @return string
	 */
	public function getIndexingSessionId()
	{
		return StringHelper::UUID();
	}

	/**
	 * Gets the index list for a source.
	 *
	 * @param $sessionId
	 * @param $sourceId
	 * @param $directory
	 *
	 * @return array
	 */
	public function getIndexListForSource($sessionId, $sourceId, $directory = '')
	{
		try
		{
			$sourceType =  Craft::$app->assetSources->getSourceTypeById($sourceId);

			$fileList = $sourceType->getFileList($directory);

			$fileList = array_filter($fileList, function($value)
			{
				$path = $value['path'];
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

			// Sort by number of slashes to ensure that parent folders are listed earlier than their children
			usort($fileList, function ($a, $b)
			{
				$a = substr_count($a['path'], '/');
				$b = substr_count($b['path'], '/');

				return ($a == $b ? 0 : ($a < $b ? -1 : 1));
			});

			$bucketFolders = [];
			$skippedFiles = [];
			$offset = 0;
			$total = 0;

			foreach ($fileList as $file)
			{
				$allowedByFilter = !preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $file['basename']);
				$allowedByName = $file['basename'] == AssetsHelper::prepareAssetName($file['basename'], $file['type'] != 'dir');

				if ($allowedByFilter && $allowedByName)
				{
					if ($file['type'] == 'dir')
					{
						$bucketFolders[$file['path']] = true;
					}
					else
					{
						$indexEntry = array(
							'sourceId' => $sourceId,
							'sessionId' => $sessionId,
							'offset' => $offset++,
							'uri' => $file['path'],
							'size' => $file['size'],
							'timestamp' => $file['timestamp']
						);

						Craft::$app->assetIndexing->storeIndexEntry($indexEntry);
						$total++;
					}
				}
				else
				{
					$skippedFiles[] = $sourceType->model->name.'/'.$file['path'];
				}
			}

			$indexedFolderIds = array();
			$indexedFolderIds[$this->ensureTopFolder($sourceType->model)] = true;

			// Ensure folders are in the DB
			foreach ($bucketFolders as $fullPath => $nothing)
			{
				$folderId = Craft::$app->assets->ensureFolderByFullPathAndSourceId(rtrim($fullPath, '/').'/', $sourceId);
				$indexedFolderIds[$folderId] = true;
			}

			$missingFolders = array();

			$allFolders = Craft::$app->assets->findFolders(array(
				'sourceId' => $sourceId
			));

			foreach ($allFolders as $folderModel)
			{
				if (!isset($indexedFolderIds[$folderModel->id]))
				{
					$missingFolders[$folderModel->id] = $sourceType->model->name.'/'.$folderModel->path;
				}
			}

			return ['sourceId' => $sourceId, 'total' => $total, 'missingFolders' => $missingFolders, 'skippedFiles' => $skippedFiles];
		}
		catch (\Exception $exception)
		{
			return ['error' => $exception->getMessage()];
		}
	}

	/**
	 * Process index for a source.
	 *
	 * @param $sessionId
	 * @param $offset
	 * @param $sourceId
	 *
	 * @return mixed
	 */
	public function processIndexForSource($sessionId, $offset, $sourceId)
	{
		$sourceType = Craft::$app->assetSources->getSourceTypeById($sourceId);

		$indexEntryModel = $this->getIndexEntry($sourceId, $sessionId, $offset);

		if (empty($indexEntryModel))
		{
			return false;
		}

		$uriPath = $indexEntryModel->uri;
		$assetModel = $this->_indexFile($sourceType, $uriPath);

		if ($assetModel)
		{
			$this->updateIndexEntryRecordId($indexEntryModel->id, $assetModel->id);

			$assetModel->size = $indexEntryModel->size;
			$timeModified = new DateTime($indexEntryModel->timestamp);

			if ($assetModel->kind == 'image')
			{
				$targetPath = $assetModel->getImageTransformSourcePath();

				if ($assetModel->dateModified != $timeModified || !IOHelper::fileExists($targetPath))
				{
					if (!$sourceType->isLocal())
					{
						$sourceType->saveFile($uriPath, $targetPath);

						// Store the local source for now and set it up for deleting, if needed
						Craft::$app->assetTransforms->storeLocalSource($targetPath);
						Craft::$app->assetTransforms->queueSourceForDeletingIfNecessary($targetPath);
					}

					clearstatcache();
					list ($assetModel->width, $assetModel->height) = getimagesize($targetPath);
				}
			}

			$assetModel->dateModified = $timeModified;

			Craft::$app->assets->saveAsset($assetModel);

			return ['result' => $assetModel->id];
		}

		return ['result' => false];
	}

	/**
	 * Ensures a top level folder exists that matches the model.
	 *
	 * @param AssetSource $model
	 *
	 * @return int
	 */
	public function ensureTopFolder(AssetSource $model)
	{
		$folder = AssetFolder::findOne(['name' => $model->name,'sourceId' => $model->id]);

		if (empty($folder))
		{
			$folder = new AssetFolder();
			$folder->sourceId = $model->id;
			$folder->parentId = null;
			$folder->name = $model->name;
			$folder->path = '';
			$folder->save();
		}

		return $folder->id;
	}

	/**
	 * Store an index entry.
	 *
	 * @param $data
	 */
	public function storeIndexEntry($data)
	{
		$entry = new AssetIndexDataRecord();

		foreach ($data as $key => $value)
		{
			$entry->setAttribute($key, $value);
		}

		$entry->save();
	}

	/**
	 * Return an index model.
	 *
	 * @param $sourceId
	 * @param $sessionId
	 * @param $offset
	 *
	 * @return AssetIndexDataModel|bool
	 */
	public function getIndexEntry($sourceId, $sessionId, $offset)
	{
		$record = AssetIndexDataRecord::findOne([
				'sourceId' => $sourceId,
				'sessionId' => $sessionId,
				'offset' => $offset
			]
		);

		if ($record)
		{
			return AssetIndexDataModel::create($record);
		}

		return false;
	}

	/**
	 * @param $entryId
	 * @param $recordId
	 *
	 * @return null
	 */
	public function updateIndexEntryRecordId($entryId, $recordId)
	{
		Craft::$app->getDb()->createCommand()->update('{{%assetindexdata}}', array('recordId' => $recordId), array('id' => $entryId))->execute();
	}


	/**
	 * Return a list of missing files for an indexing session.
	 *
	 * @param $sources
	 * @param $sessionId
	 *
	 * @return array
	 */
	public function getMissingFiles($sources, $sessionId)
	{
		$output = array();

		// Load the record IDs of the files that were indexed.
		$processedFiles = (new Query())
			->select('recordId')
			->from('{{%assetindexdata}}')
			->where('sessionId = :sessionId AND recordId IS NOT NULL', array(':sessionId' => $sessionId))
			->column();

		// Flip for faster lookup
		$processedFiles = array_flip($processedFiles);

		$fileEntries = (new Query())
			->select('fi.sourceId, fi.id AS fileId, fi.filename, fo.path, s.name AS sourceName')
			->from('{{%assets}} AS fi')
			->innerJoin('{{%assetfolders}} AS fo', 'fi.folderId = fo.id')
			->innerJoin('{{%assetsources}} AS s', 's.id = fi.sourceId')
			->where(array('in', 'fi.sourceId', $sources))
			->all();

		foreach ($fileEntries as $fileEntry)
		{
			if (!isset($processedFiles[$fileEntry['fileId']]))
			{
				$output[$fileEntry['fileId']] = $fileEntry['sourceName'].'/'.$fileEntry['path'].$fileEntry['filename'];
			}
		}

		return $output;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Indexes a file.
	 *
	 * @param BaseAssetSourceType $sourceType The SourceType.
	 * @param string              $uriPath    The URI path fo the file to index.
	 *
	 * @return Asset|bool
	 */
	private function _indexFile($sourceType, $uriPath)
	{
		$extension = IOHelper::getExtension($uriPath);

		if (IOHelper::isExtensionAllowed($extension))
		{
			$parts = explode('/', $uriPath);
			$filename = array_pop($parts);

			$searchFullPath = join('/', $parts).(empty($parts) ? '' : '/');

			if (empty($searchFullPath))
			{
				$parentId = ':empty:';
			}
			else
			{
				$parentId = false;
			}

			$parentFolder = Craft::$app->assets->findFolder(array(
				'sourceId' => $sourceType->model->id,
				'path' => $searchFullPath,
				'parentId' => $parentId
			));

			if (empty($parentFolder))
			{
				return false;
			}

			$folderId = $parentFolder->id;

			$assetModel = Craft::$app->assets->findFile(array(
				'folderId' => $folderId,
				'filename' => $filename
			));

			if (is_null($assetModel))
			{
				$assetModel = new Asset();
				$assetModel->sourceId = $sourceType->model->id;
				$assetModel->folderId = $folderId;
				$assetModel->filename = $filename;
				$assetModel->kind = IOHelper::getFileKind($extension);
				$assetModel->indexInProgress = true;
				Craft::$app->assets->saveAsset($assetModel);
			}

			return $assetModel;
		}

		return false;
	}
}
