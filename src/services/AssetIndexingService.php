<?php
namespace Craft;

/**
 *
 */
class AssetIndexingService extends BaseApplicationComponent
{

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
	 * Gets index list for a source.
	 *
	 * @param $sessionId
	 * @param $sourceId
	 * @return array
	 */
	public function getIndexListForSource($sessionId, $sourceId)
	{
		return craft()->assetSources->getSourceTypeById($sourceId)->startIndex($sessionId);
	}

	/**
	 * Process index for a source.
	 *
	 * @param $sessionId
	 * @param $offset
	 * @param $sourceId
	 * @return mixed
	 */
	public function processIndexForSource($sessionId, $offset, $sourceId)
	{
		return array('result' => craft()->assetSources->getSourceTypeById($sourceId)->processIndex($sessionId, $offset));
	}

	/**
	 * Ensures a top level folder exists that matches the model
	 * @param AssetSourceModel $model
	 * @return int
	 */
	public function ensureTopFolder(AssetSourceModel $model)
	{
		$folder = AssetFolderRecord::model()->findByAttributes(
			array(
				'name' => $model->name,
				'sourceId' => $model->id
			)
		);

		if (empty($folder))
		{
			$folder = new AssetFolderRecord();
			$folder->sourceId = $model->id;
			$folder->parentId = null;
			$folder->name = $model->name;
			$folder->fullPath = "";
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
	 * @return AssetIndexDataModel|bool
	 */
	public function getIndexEntry($sourceId, $sessionId, $offset)
	{
		$record = AssetIndexDataRecord::model()->findByAttributes(
			array(
				'sourceId' => $sourceId,
				'sessionId' => $sessionId,
				'offset' => $offset
			)
		);

		if ($record)
		{
			return AssetIndexDataModel::populateModel($record);
		}

		return false;
	}

	/**
	 * @param $entryId
	 * @param $recordId
	 */
	public function updateIndexEntryRecordId($entryId, $recordId)
	{
		craft()->db->createCommand()->update('assetindexdata', array('recordId' => $recordId), array('id' => $entryId));
	}

	/**
	 * Finish indexing by sessionId, source list and a JSON command.
	 *
	 * @param $sessionId
	 * @param $sources
	 * @param $command
	 * @return array $output
	 * @throws Exception
	 */
	public function finishIndex($sessionId, $sources, $command)
	{
		$command = JsonHelper::decode($command);
		$output = array();

		switch ($command['command'])
		{
			case 'delete':
			{
				if ( ! empty($command['fileIds']))
				{
					foreach ($command['fileIds'] as &$fileId)
					{
						$fileId = (int) $fileId;
					}

					AssetFileRecord::model()->deleteAll('id IN (' . implode(",", $command['fileIds']).')');

					// TODO: delete all created sizes as well
					foreach ($command['fileIds'] as $fileId)
					{
						$files = glob(craft()->path->getAssetsImageSourcePath().$fileId.'.*');
						foreach ($files as $file)
						{
							IOHelper::deleteFile($file);
						}

						IOHelper::deleteFolder(craft()->path->getAssetsThumbsPath().$fileId);

						craft()->elements->deleteElementById($fileId);
					}
				}

				if ( ! empty($command['folderIds']))
				{
					foreach ($command['folderIds'] as &$folderId)
					{
						$folderId = (int) $folderId;
					}

					$folders = craft()->assets->findFolders(array('id' => $command['folderIds']));

					foreach ($folders as $folder)
					{
						$fileIds = craft()->db->createCommand()
							->select('fi.id')
							->from('assetfiles AS fi')
							->join('assetfolders AS fo', 'fi.folderId = fo.id AND fo.fullPath LIKE :fullPath AND fo.sourceId = :sourceId',
								array(
									':fullPath' => $folder->fullPath.'%',
									':sourceId' => $folder->sourceId
								))
							->queryColumn();
					}

					AssetFolderRecord::model()->deleteAll('id IN ('.implode(",", $command['folderIds']).')');
				}

				$output['success'] = TRUE;
				break;
			}

			case 'statistics':
			{
				$output['files'] = $this->getMissingFiles($sources, $sessionId);

				craft()->db->createCommand()->delete('assetindexdata', array('sessionId' => $sessionId));

				break;
			}

			default:
			{
				throw new Exception(Craft::t('Unknown indexing command!'));
			}
		}

		return $output;
	}

	/**
	 * Return a list of missing files for an indexing session.
	 *
	 * @param $sources
	 * @param $sessionId
	 * @return array
	 */
	public function getMissingFiles($sources, $sessionId)
	{
		$output = array();

		// Load the record IDs of the files that were indexed.
		$processedFiles = craft()->db->createCommand()
			->select('recordId')
			->from('assetindexdata')
			->where('sessionId = :sessionId AND recordId IS NOT NULL', array(':sessionId' => $sessionId))
			->queryColumn();

		$processedFiles = array_flip($processedFiles);

		$fileEntries = craft()->db->createCommand()
			->select('fi.sourceId, fi.id AS fileId, fi.filename, fo.fullPath, s.name AS sourceName')
			->from('assetfiles AS fi')
			->join('assetfolders AS fo', 'fi.folderId = fo.id')
			->join('assetsources AS s', 's.id = fi.sourceId')
			->where(array('in', 'fi.sourceId', $sources))
			->queryAll();

		foreach ($fileEntries as $fileEntry)
		{
			if (!isset($processedFiles[$fileEntry['fileId']]))
			{
				$output[$fileEntry['fileId']] = $fileEntry['sourceName'].'/'.$fileEntry['fullPath'].$fileEntry['filename'];
			}
		}

		return $output;
	}

	/**
	 * Remove obsolete file records.
	 *
	 * @param $fileIds
	 */
	public function removeObsoleteFileRecords($fileIds)
	{
		craft()->db->createCommand()->delete('assettransformindex', array('in', 'fileId', $fileIds));
		craft()->db->createCommand()->delete('assetfiles', array('in', 'id', $fileIds));
	}

	/**
	 * Remove obsolete folder records.
	 *
	 * @param $folderIds
	 */
	public function removeObsoleteFolderRecords($folderIds)
	{
		craft()->db->createCommand()->delete('assetfolders', array('in', 'id', $folderIds));
	}

}
