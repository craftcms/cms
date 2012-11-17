<?php
namespace Blocks;

/**
 *
 */
class EntryRevisionsService extends BaseApplicationComponent
{
	// -------------------------------------------
	//  Drafts
	// -------------------------------------------

	/**
	 * Returns a draft by its ID.
	 *
	 * @param int $draftId
	 * @return EntryDraftModel|null
	 */
	public function getDraftById($draftId)
	{
		$draftRecord = EntryDraftRecord::model()->findById($draftId);

		if ($draftRecord)
		{
			return EntryDraftModel::populateModel($draftRecord);
		}
	}

	/**
	 * Returns a draft by its offset.
	 *
	 * @param int $entryId
	 * @param int $offset
	 * @return EntryDraftModel|null
	 */
	public function getDraftByOffset($entryId, $offset = 0)
	{
		$draftRecord = EntryDraftRecord::model()->find(array(
			'condition' => 'entryId = :entryId AND language = :language',
			'params' => array(':entryId' => $entryId, ':language' => blx()->language),
			'offset' => $offset
		));

		if ($draftRecord)
		{
			return EntryDraftModel::populateModel($draftRecord);
		}
	}

	/**
	 * Returns drafts by an entry ID.
	 *
	 * @param int $entryId
	 * @return array
	 */
	public function getDraftsByEntryId($entryId)
	{
		$draftRecords = EntryDraftRecord::model()->findAllByAttributes(array(
			'entryId' => $entryId,
			'language' => blx()->language,
		));

		return EntryDraftModel::populateModels($draftRecords, 'draftId');
	}

	/**
	 * Saves a draft.
	 *
	 * @param EntryDraftModel $draft
	 * @return bool
	 */
	public function saveDraft(EntryDraftModel $draft)
	{
		$draftRecord = $this->_getDraftRecord($draft);
		$draftRecord->data = $this->_getRevisionData($draft);

		if ($draftRecord->save())
		{
			$draft->draftId = $draftRecord->id;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Publishes a draft.
	 *
	 * @param EntryDraftModel $draft
	 * @return bool
	 */
	public function publishDraft(EntryDraftModel $draft)
	{
		$draftRecord = $this->_getDraftRecord($draft);

		if (blx()->entries->saveEntry($draft))
		{
			$draftRecord->delete();
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns a draft record.
	 *
	 * @access private
	 * @param EntryDraftModel $draft
	 * @throws Exception
	 * @return EntryDraftRecord
	 */
	private function _getDraftRecord(EntryDraftModel $draft)
	{
		if ($draft->draftId)
		{
			$draftRecord = EntryDraftRecord::model()->findById($draft->draftId);

			if (!$draftRecord)
			{
				throw new Exception(Blocks::t('No draft exists with the ID “{id}”', array('id' => $draft->draftId)));
			}
		}
		else
		{
			$draftRecord = new EntryDraftRecord();
			$draftRecord->entryId = $draft->id;
			$draftRecord->sectionId = $draft->sectionId;
			$draftRecord->creatorId = $draft->creatorId;
			$draftRecord->language = $draft->language;
		}

		return $draftRecord;
	}

	// -------------------------------------------
	//  Versions
	// -------------------------------------------

	/**
	 * Returns a version by its ID.
	 *
	 * @param int $versionId
	 * @return EntryDraftModel|null
	 */
	public function getVersionById($versionId)
	{
		$versionRecord = EntryVersionRecord::model()->findById($versionId);

		if ($versionRecord)
		{
			return EntryVersionModel::populateModel($versionRecord);
		}
	}

	/**
	 * Returns a version by its offset.
	 *
	 * @param int $entryId
	 * @param int $versionNum
	 * @return EntryVersionModel|null
	 */
	public function getVersionByOffset($entryId, $offset = 0)
	{
		$versionRecord = EntryVersionRecord::model()->findByAttributes(array(
			'entryId' => $entryId,
			'language' => blx()->language,
		));

		if ($versionRecord)
		{
			return EntryVersionModel::populateModel($versionRecord);
		}
	}

	/**
	 * Returns versions by an entry ID.
	 *
	 * @param int $entryId
	 * @return array
	 */
	public function getVersionsByEntryId($entryId)
	{
		$versionRecords = EntryVersionRecord::model()->findAllByAttributes(array(
			'entryId' => $entryId,
			'language' => blx()->language,
		));

		return EntryVersionModel::populateModels($versionRecords, 'versionId');
	}

	/**
	 * Saves a new versoin.
	 *
	 * @param EntryModel $entry
	 * @return bool
	 */
	public function saveVersion(EntryModel $entry)
	{
		$versionRecord = new EntryVersionRecord();
		$versionRecord->entryId = $entry->id;
		$versionRecord->sectionId = $entry->sectionId;
		$versionRecord->creatorId = blx()->account->getCurrentUser()->id;
		$versionRecord->language = $entry->language;
		$versionRecord->data = $this->_getRevisionData($entry);
		return $versionRecord->save();
	}

	/**
	 * Returns an array of all the revision data for a draft or version.
	 *
	 * @param EntryDraftModel|EntryVersionModel $revision
	 * @return array
	 */
	public function _getRevisionData($revision)
	{
		$postDate = DateTimeHelper::normalizeDate($revision->postDate);
		$expiryDate = DateTimeHelper::normalizeDate($revision->expiryDate);

		$revisionData = array(
			'authorId' => $revision->authorId,
			'title' => $revision->title,
			'slug' => $revision->slug,
			'postDate' => ($postDate ? $postDate->getTimestamp() : null),
			'expiryDate' => ($expiryDate ? $expiryDate->getTimestamp() : null),
			'enabled' => $revision->enabled,
			'tags' => $revision->tags,
			'blocks' => array(),
		);

		$blocks = blx()->sections->getBlocksBySectionId($revision->sectionId);
		$content = $revision->getRawContent();

		foreach ($blocks as $block)
		{
			if (isset($content[$block->handle]))
			{
				$revisionData['blocks'][$block->id] = $content[$block->handle];
			}
			else
			{
				$revisionData['blocks'][$block->id] = null;
			}
		}

		return $revisionData;
	}
}
