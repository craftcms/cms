<?php
namespace Craft;

craft()->requirePackage(CraftPackage::PublishPro);

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
			'condition' => 'entryId = :entryId AND locale = :locale',
			'params' => array(':entryId' => $entryId, ':locale' => craft()->i18n->getPrimarySiteLocale()),
			'offset' => $offset
		));

		if ($draftRecord)
		{
			return EntryDraftModel::populateModel($draftRecord);
		}
	}

	/**
	 * Returns drafts of a given entry.
	 *
	 * @param int $entryId
	 * @param string $localeId
	 * @return array
	 */
	public function getDraftsByEntryId($entryId, $localeId = null)
	{
		if (!$localeId)
		{
			$localeId = craft()->i18n->getPrimarySiteLocale();
		}

		$draftRecords = EntryDraftRecord::model()->findAllByAttributes(array(
			'entryId' => $entryId,
			'locale'  => $localeId,
		));

		return EntryDraftModel::populateModels($draftRecords);
	}

	/**
	 * Returns the drafts of a given entry that are editable by the current user.
	 *
	 * @param int $entryId
	 * @param string $localeId
	 * @return array
	 */
	public function getEditableDraftsByEntryId($entryId, $localeId = null)
	{
		$editableDrafts = array();
		$user = craft()->userSession->getUser();

		if ($user)
		{
			$allDrafts = $this->getDraftsByEntryId($entryId, $localeId);

			foreach ($allDrafts as $draft)
			{
				if ($draft->creatorId == $user->id || $user->can('editPeerEntryDrafts:'.$draft->sectionId))
				{
					$editableDrafts[] = $draft;
				}
			}
		}

		return $editableDrafts;
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
		$isNewDraft = !$draft->draftId;

		if ($draftRecord->save())
		{
			$draft->draftId = $draftRecord->id;

			// Fire an 'onSaveDraft' event
			$this->onSaveDraft(new Event($this, array(
				'draft'      => $draft,
				'isNewDraft' => $isNewDraft
			)));

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
		if (craft()->entries->saveEntry($draft))
		{
			// Fire an 'onPublishDraft' event
			$this->onPublishDraft(new Event($this, array(
				'draft'      => $draft,
			)));

			$this->deleteDraft($draft);
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a draft by it's model.
	 * @param EntryDraftModel $draft
	 */
	public function deleteDraft(EntryDraftModel $draft)
	{
		$draftRecord = $this->_getDraftRecord($draft);

		// Fire an 'onBeforeDeleteDraft' event
		$this->onBeforeDeleteDraft(new Event($this, array(
			'draft'      => $draft,
		)));

		$draftRecord->delete();

		// Fire an 'onAfterDeleteDraft' event
		$this->onAfterDeleteDraft(new Event($this, array(
			'draft'      => $draft,
		)));
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
				throw new Exception(Craft::t('No draft exists with the ID “{id}”', array('id' => $draft->draftId)));
			}
		}
		else
		{
			$draftRecord = new EntryDraftRecord();
			$draftRecord->entryId   = $draft->id;
			$draftRecord->sectionId = $draft->sectionId;
			$draftRecord->creatorId = $draft->creatorId;
			$draftRecord->locale    = $draft->locale;
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
	 * @param int $offset
	 * @return EntryVersionModel|null
	 */
	public function getVersionByOffset($entryId, $offset = 0)
	{
		$versionRecord = EntryVersionRecord::model()->findByAttributes(array(
			'entryId' => $entryId,
			'locale'  => craft()->i18n->getPrimarySiteLocale(),
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
	 * @param string $localeId
	 * @return array
	 */
	public function getVersionsByEntryId($entryId, $localeId)
	{
		if (!$localeId)
		{
			$localeId = craft()->i18n->getPrimarySiteLocale();
		}

		$versionRecords = EntryVersionRecord::model()->findAllByAttributes(array(
			'entryId' => $entryId,
			'locale'  => $localeId,
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
		$versionRecord->creatorId = craft()->userSession->getUser()->id;
		$versionRecord->locale = $entry->locale;
		$versionRecord->data = $this->_getRevisionData($entry);
		return $versionRecord->save();
	}

	/**
	 * Fires an 'onSaveDraft' event.
	 *
	 * @param Event $event
	 */
	public function onSaveDraft(Event $event)
	{
		$this->raiseEvent('onSaveDraft', $event);
	}

	/**
	 * Fires an 'onPublishDraft' event.
	 *
	 * @param Event $event
	 */
	public function onPublishDraft(Event $event)
	{
		$this->raiseEvent('onPublishDraft', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteDraft' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeDeleteDraft(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteDraft', $event);
	}

	/**
	 * Fires an 'onAfterDeleteDraft' event.
	 *
	 * @param Event $event
	 */
	public function onAfterDeleteDraft(Event $event)
	{
		$this->raiseEvent('onAfterDeleteDraft', $event);
	}

	/**
	 * Returns an array of all the revision data for a draft or version.
	 *
	 * @param EntryDraftModel|EntryVersionModel $revision
	 * @return array
	 */
	private function _getRevisionData($revision)
	{
		$revisionData = array(
			'authorId'   => $revision->authorId,
			'title'      => $revision->title,
			'slug'       => $revision->slug,
			'postDate'   => ($revision->postDate   ? $revision->postDate->getTimestamp()   : null),
			'expiryDate' => ($revision->expiryDate ? $revision->expiryDate->getTimestamp() : null),
			'enabled'    => $revision->enabled,
			'fields'     => array(),
		);

		$content = $revision->getContent()->getAttributes(null, true);

		foreach (craft()->fields->getAllFields() as $field)
		{
			if (isset($content[$field->handle]) && $content[$field->handle] !== null)
			{
				$revisionData['fields'][$field->id] = $content[$field->handle];
			}
		}

		return $revisionData;
	}
}
