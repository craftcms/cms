<?php
namespace Blocks;

/**
 *
 */
class AssetIndexingService extends BaseApplicationComponent
{

	/**
	 * Returns a unique indexing session id
	 * @return string
	 */
	public function getIndexingSessionId()
	{
		return StringHelper::UUID();
	}

	/**
	 * Gets index list for a source
	 * @param $sessionId
	 * @param $sourceId
	 * @return array
	 */
	public function getIndexListForSource($sessionId, $sourceId)
	{
		return blx()->assetSources->getSourceTypeById($sourceId)->startIndex($sessionId);
	}

	/**
	 * Process index for a source
	 * @param $sessionId
	 * @param $offset
	 * @param $sourceId
	 * @return mixed
	 */
	public function processIndexForSource($sessionId, $offset, $sourceId)
	{
		return array('result' => blx()->assetSources->getSourceTypeById($sourceId)->processIndex($sessionId, $offset));
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
	 * Store an index entry
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
	 * Return an index model
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
		blx()->db->createCommand()->update('assetindexdata', array('recordId' => $recordId), array('id' => $entryId));
	}
}
