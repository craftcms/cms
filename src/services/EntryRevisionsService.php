<?php
namespace Craft;

/**
 * Class EntryRevisionsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class EntryRevisionsService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns a draft by its ID.
	 *
	 * @param int $draftId
	 *
	 * @return EntryDraftModel|null
	 */
	public function getDraftById($draftId)
	{
		$draftRecord = EntryDraftRecord::model()->findById($draftId);

		if ($draftRecord)
		{
			$draft = EntryDraftModel::populateModel($draftRecord);

			// This is a little hacky, but fixes a bug where entries are getting the wrong URL when a draft is published
			// inside of a structured section since the selected URL Format depends on the entry's level, and there's no
			// reason to store the level along with the other draft data.
			$entry = craft()->entries->getEntryById($draftRecord->entryId, $draftRecord->locale);

			$draft->root  = $entry->root;
			$draft->lft   = $entry->lft;
			$draft->rgt   = $entry->rgt;
			$draft->level = $entry->level;

			return $draft;
		}
	}

	/**
	 * Returns a draft by its offset.
	 *
	 * @param int $entryId
	 * @param int $offset
	 *
	 * @deprecated Deprecated in 2.1.
	 * @return EntryDraftModel|null
	 */
	public function getDraftByOffset($entryId, $offset = 0)
	{
		craft()->deprecator->log('EntryRevisionsService::getDraftByOffset()', 'EntryRevisionsService::getDraftByOffset() has been deprecated.');

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
	 * @param int    $entryId
	 * @param string $localeId
	 *
	 * @return array
	 */
	public function getDraftsByEntryId($entryId, $localeId = null)
	{
		if (!$localeId)
		{
			$localeId = craft()->i18n->getPrimarySiteLocale();
		}

		$drafts = array();

		$results = craft()->db->createCommand()
			->select('*')
			->from('entrydrafts')
			->where(array('and', 'entryId = :entryId', 'locale = :locale'), array(':entryId' => $entryId, ':locale' => $localeId))
			->order('name asc')
			->queryAll();

		foreach ($results as $result)
		{
			$result['data'] = JsonHelper::decode($result['data']);

			// Don't initialize the content
			unset($result['data']['fields']);

			$drafts[] = EntryDraftModel::populateModel($result);
		}

		return $drafts;
	}

	/**
	 * Returns the drafts of a given entry that are editable by the current user.
	 *
	 * @param int    $entryId
	 * @param string $localeId
	 *
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
	 *
	 * @return bool
	 */
	public function saveDraft(EntryDraftModel $draft)
	{
		$draftRecord = $this->_getDraftRecord($draft);

		if (!$draft->name && $draft->id)
		{
			// Get the total number of existing drafts for this entry/locale
			$totalDrafts = craft()->db->createCommand()
				->from('entrydrafts')
				->where(
					array('and', 'entryId = :entryId', 'locale = :locale'),
					array(':entryId' => $draft->id, ':locale' => $draft->locale)
				)
				->count('id');

			$draft->name = Craft::t('Draft {num}', array('num' => $totalDrafts + 1));
		}

		$draftRecord->name = $draft->name;
		$draftRecord->notes = $draft->revisionNotes;
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
	 *
	 * @return bool
	 */
	public function publishDraft(EntryDraftModel $draft)
	{
		// If this is a single, we'll have to set the title manually
		if ($draft->getSection()->type == SectionType::Single)
		{
			$draft->getContent()->title = $draft->getSection()->name;
		}

		// Set the version notes
		if (!$draft->revisionNotes)
		{
			$draft->revisionNotes = Craft::t('Published draft “{name}”.', array('name' => $draft->name));
		}

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
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeDeleteDraft' event
			$event = new Event($this, array(
				'draft' => $draft,
			));

			$this->onBeforeDeleteDraft($event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				$draftRecord = $this->_getDraftRecord($draft);
				$draftRecord->delete();

				$success = true;
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we deleted the draft, in case something changed
			// in onBeforeDeleteDraft
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

		if ($success)
		{
			// Fire an 'onDeleteDraft' event
			$this->onDeleteDraft(new Event($this, array(
				'draft' => $draft,
			)));

			//
			if ($this->hasEventHandler('onAfterDeleteDraft'))
			{
				// Fire an 'onAfterDeleteDraft' event (deprecated)
				$this->onAfterDeleteDraft(new Event($this, array(
					'draft' => $draft,
				)));
			}
		}

		return $success;
	}

	/**
	 * Returns a version by its ID.
	 *
	 * @param int $versionId
	 *
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
	 *
	 * @deprecated Deprecated in 2.1.
	 * @return EntryVersionModel|null
	 */
	public function getVersionByOffset($entryId, $offset = 0)
	{
		craft()->deprecator->log('EntryRevisionsService::getVersionByOffset()', 'EntryRevisionsService::getVersionByOffset() has been deprecated.');

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
	 * @param int      $entryId        The entry ID to search for.
	 * @param string   $localeId       The locale ID to search for.
	 * @param int|null $limit          The limit on the number of versions to retrieve.
	 * @param bool     $includeCurrent Whether to include the current "top" version of the entry.
	 *
	 * @return array
	 */
	public function getVersionsByEntryId($entryId, $localeId, $limit = null, $includeCurrent = false)
	{
		if (!$localeId)
		{
			$localeId = craft()->i18n->getPrimarySiteLocale();
		}

		$versions = array();

		$query = craft()->db->createCommand()
			->select('*')
			->from('entryversions')
			->where(array('and', 'entryId = :entryId', 'locale = :locale'), array(':entryId' => $entryId, ':locale' => $localeId))
			->order('dateCreated desc')
			->limit($limit);

		if (!$includeCurrent)
		{
			$query->offset(1);
		}

		$results = $query->queryAll();

		foreach ($results as $result)
		{
			$result['data'] = JsonHelper::decode($result['data']);

			// Don't initialize the content
			unset($result['data']['fields']);

			$versions[] = EntryVersionModel::populateModel($result);
		}

		return $versions;
	}

	/**
	 * Saves a new version.
	 *
	 * @param EntryModel $entry
	 *
	 * @return bool
	 */
	public function saveVersion(EntryModel $entry)
	{
		// Get the total number of existing versions for this entry/locale
		$totalVersions = craft()->db->createCommand()
			->from('entryversions')
			->where(
				array('and', 'entryId = :entryId', 'locale = :locale'),
				array(':entryId' => $entry->id, ':locale' => $entry->locale)
			)
			->count('id');

		$versionRecord = new EntryVersionRecord();
		$versionRecord->entryId = $entry->id;
		$versionRecord->sectionId = $entry->sectionId;
		$versionRecord->creatorId = craft()->userSession->getUser() ? craft()->userSession->getUser()->id : $entry->authorId;
		$versionRecord->locale = $entry->locale;
		$versionRecord->num = $totalVersions + 1;
		$versionRecord->data = $this->_getRevisionData($entry);
		$versionRecord->notes = $entry->revisionNotes;

		return $versionRecord->save();
	}

	/**
	 * Reverts an entry to a version.
	 *
	 * @param EntryVersionModel $version
	 *
	 * @return bool
	 */
	public function revertEntryToVersion(EntryVersionModel $version)
	{
		// If this is a single, we'll have to set the title manually
		if ($version->getSection()->type == SectionType::Single)
		{
			$version->getContent()->title = $version->getSection()->name;
		}

		// Set the version notes
		$version->revisionNotes = Craft::t('Reverted version {num}.', array('num' => $version->num));

		if (craft()->entries->saveEntry($version))
		{
			// Fire an 'onRevertEntryToVersion' event
			$this->onRevertEntryToVersion(new Event($this, array(
				'version' => $version,
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fires an 'onSaveDraft' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveDraft(Event $event)
	{
		$this->raiseEvent('onSaveDraft', $event);
	}

	/**
	 * Fires an 'onPublishDraft' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onPublishDraft(Event $event)
	{
		$this->raiseEvent('onPublishDraft', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteDraft' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteDraft(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteDraft', $event);
	}

	/**
	 * Fires an 'onDeleteDraft' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeleteDraft(Event $event)
	{
		$this->raiseEvent('onDeleteDraft', $event);
	}

	/**
	 * Fires an 'onAfterDeleteDraft' event.
	 *
	 * @param Event $event
	 *
	 * @deprecated Deprecated in 2.3. Use {@link onDeleteDraft() `entryRevisions.onDeleteDraft`} instead.
	 * @return null
	 */
	public function onAfterDeleteDraft(Event $event)
	{
		$this->raiseEvent('onDeleteDraft', $event);
	}

	/**
	 * Fires an 'onRevertEntryToVersion' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onRevertEntryToVersion(Event $event)
	{
		$this->raiseEvent('onRevertEntryToVersion', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a draft record.
	 *
	 * @param EntryDraftModel $draft
	 *
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
				throw new Exception(Craft::t('No draft exists with the ID “{id}”.', array('id' => $draft->draftId)));
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

	/**
	 * Returns an array of all the revision data for a draft or version.
	 *
	 * @param EntryDraftModel|EntryVersionModel $revision
	 *
	 * @return array
	 */
	private function _getRevisionData($revision)
	{
		$revisionData = array(
			'typeId'     => $revision->typeId,
			'authorId'   => $revision->authorId,
			'title'      => $revision->title,
			'slug'       => $revision->slug,
			'postDate'   => ($revision->postDate   ? $revision->postDate->getTimestamp()   : null),
			'expiryDate' => ($revision->expiryDate ? $revision->expiryDate->getTimestamp() : null),
			'enabled'    => $revision->enabled,
			'parentId'   => $revision->parentId,
			'fields'     => array(),
		);

		$content = $revision->getContentFromPost();

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
