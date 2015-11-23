<?php
namespace Craft;

/**
 * EntriesService provides APIs for managing entries in Craft.
 *
 * An instance of EntriesService is globally accessible in Craft via {@link WebApp::entries `craft()->entries`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class EntriesService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns an entry by its ID.
	 *
	 * ```php
	 * $entry = craft()->entries->getEntryById($entryId);
	 * ```
	 *
	 * @param int    $entryId  The entry’s ID.
	 * @param string $localeId The locale to fetch the entry in. Defaults to {@link WebApp::language `craft()->language`}.
	 *
	 * @return EntryModel|null The entry with the given ID, or `null` if an entry could not be found.
	 */
	public function getEntryById($entryId, $localeId = null)
	{
		return craft()->elements->getElementById($entryId, ElementType::Entry, $localeId);
	}

	/**
	 * Saves a new or existing entry.
	 *
	 * ```php
	 * $entry = new EntryModel();
	 * $entry->sectionId = 10;
	 * $entry->typeId    = 1;
	 * $entry->authorId  = 5;
	 * $entry->enabled   = true;
	 *
	 * $entry->getContent()->title = "Hello World!";
	 *
	 * $entry->setContentFromPost(array(
	 *     'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
	 * ));
	 *
	 * $success = craft()->entries->saveEntry($entry);
	 *
	 * if (!$success)
	 * {
	 *     Craft::log('Couldn’t save the entry "'.$entry->title.'"', LogLevel::Error);
	 * }
	 * ```
	 *
	 * @param EntryModel $entry The entry to be saved.
	 *
	 * @throws \Exception
	 * @return bool Whether the entry was saved successfully.
	 */
	public function saveEntry(EntryModel $entry)
	{
		$isNewEntry = !$entry->id;

		$hasNewParent = $this->_checkForNewParent($entry);

		if ($hasNewParent)
		{
			if ($entry->parentId)
			{
				$parentEntry = $this->getEntryById($entry->parentId, $entry->locale);

				if (!$parentEntry)
				{
					throw new Exception(Craft::t('No entry exists with the ID “{id}”.', array('id' => $entry->parentId)));
				}
			}
			else
			{
				$parentEntry = null;
			}

			$entry->setParent($parentEntry);
		}

		// Get the entry record
		if (!$isNewEntry)
		{
			$entryRecord = EntryRecord::model()->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”.', array('id' => $entry->id)));
			}
		}
		else
		{
			$entryRecord = new EntryRecord();
		}

		// Get the section
		$section = craft()->sections->getSectionById($entry->sectionId);

		if (!$section)
		{
			throw new Exception(Craft::t('No section exists with the ID “{id}”.', array('id' => $entry->sectionId)));
		}

		// Verify that the section is available in this locale
		$sectionLocales = $section->getLocales();

		if (!isset($sectionLocales[$entry->locale]))
		{
			throw new Exception(Craft::t('The section “{section}” is not enabled for the locale {locale}', array('section' => $section->name, 'locale' => $entry->locale)));
		}

		// Set the entry data
		$entryType = $entry->getType();

		$entryRecord->sectionId  = $entry->sectionId;

		if ($section->type == SectionType::Single)
		{
			$entryRecord->authorId   = $entry->authorId = null;
			$entryRecord->expiryDate = $entry->expiryDate = null;
		}
		else
		{
			$entryRecord->authorId   = $entry->authorId;
			$entryRecord->postDate   = $entry->postDate;
			$entryRecord->expiryDate = $entry->expiryDate;
			$entryRecord->typeId     = $entryType->id;
		}

		if ($entry->enabled && !$entryRecord->postDate)
		{
			// Default the post date to the current date/time
			$entryRecord->postDate = $entry->postDate = DateTimeHelper::currentUTCDateTime();
		}

		$entryRecord->validate();
		$entry->addErrors($entryRecord->getErrors());

		if ($entry->hasErrors())
		{
			return false;
		}

		if (!$entryType->hasTitleField)
		{
			$entry->getContent()->title = craft()->templates->renderObjectTemplate($entryType->titleFormat, $entry);
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			// Fire an 'onBeforeSaveEntry' event
			$event = new Event($this, array(
				'entry'      => $entry,
				'isNewEntry' => $isNewEntry
			));

			$this->onBeforeSaveEntry($event);

			// Is the event giving us the go-ahead?
			if ($event->performAction)
			{
				// Save the element
				$success = craft()->elements->saveElement($entry);

				// If it didn't work, rollback the transaction in case something changed in onBeforeSaveEntry
				if (!$success)
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					// If "title" has an error, check if they've defined a custom title label.
					if ($entry->getError('title'))
					{
						// Grab all of the original errors.
						$errors = $entry->getErrors();

						// Grab just the title error message.
						$originalTitleError = $errors['title'];

						// Clear the old.
						$entry->clearErrors();

						// Create the new "title" error message.
						$errors['title'] = str_replace(Craft::t('Title'), $entryType->titleLabel, $originalTitleError);

						// Add all of the errors back on the model.
						$entry->addErrors($errors);
					}

					return false;
				}

				// Now that we have an element ID, save it on the other stuff
				if ($isNewEntry)
				{
					$entryRecord->id = $entry->id;
				}

				// Save the actual entry row
				$entryRecord->save(false);

				if ($section->type == SectionType::Structure)
				{
					// Has the parent changed?
					if ($hasNewParent)
					{
						if (!$entry->parentId)
						{
							craft()->structures->appendToRoot($section->structureId, $entry);
						}
						else
						{
							craft()->structures->append($section->structureId, $entry, $parentEntry);
						}
					}

					// Update the entry's descendants, who may be using this entry's URI in their own URIs
					craft()->elements->updateDescendantSlugsAndUris($entry, true, true);
				}

				// Save a new version
				if ($section->enableVersioning)
				{
					craft()->entryRevisions->saveVersion($entry);
				}
			}
			else
			{
				$success = false;
			}

			// Commit the transaction regardless of whether we saved the entry, in case something changed
			// in onBeforeSaveEntry
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
			// Fire an 'onSaveEntry' event
			$this->onSaveEntry(new Event($this, array(
				'entry'      => $entry,
				'isNewEntry' => $isNewEntry
			)));
		}

		return $success;
	}

	/**
	 * Deletes an entry(s).
	 *
	 * @param EntryModel|EntryModel[] $entries An entry, or an array of entries, to be deleted.
	 *
	 * @throws \Exception
	 * @return bool Whether the entry deletion was successful.
	 */
	public function deleteEntry($entries)
	{
		if (!$entries)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			if (!is_array($entries))
			{
				$entries = array($entries);
			}

			$entryIds = array();

			foreach ($entries as $entry)
			{
				// Fire an 'onBeforeDeleteEntry' event
				$event = new Event($this, array(
					'entry' => $entry
				));

				$this->onBeforeDeleteEntry($event);

				if ($event->performAction)
				{
					$section = $entry->getSection();

					if ($section->type == SectionType::Structure)
					{
						// First let's move the entry's children up a level, so this doesn't mess up the structure
						$children = $entry->getChildren()->status(null)->localeEnabled(false)->limit(null)->find();

						foreach ($children as $child)
						{
							craft()->structures->moveBefore($section->structureId, $child, $entry, 'update', true);
						}
					}

					$entryIds[] = $entry->id;
				}
			}

			if ($entryIds)
			{
				// Delete 'em
				$success = craft()->elements->deleteElementById($entryIds);
			}
			else
			{
				$success = false;
			}

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
			foreach ($entries as $entry)
			{
				// Fire an 'onDeleteEntry' event
				$this->onDeleteEntry(new Event($this, array(
					'entry' => $entry
				)));
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes an entry(s) by its ID.
	 *
	 * @param int|array $entryId The ID of an entry to delete, or an array of entry IDs.
	 *
	 * @return bool Whether the entry deletion was successful.
	 */
	public function deleteEntryById($entryId)
	{
		if (!$entryId)
		{
			return false;
		}

		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->id = $entryId;
		$criteria->limit = null;
		$criteria->status = null;
		$criteria->localeEnabled = false;
		$entries = $criteria->find();

		if ($entries)
		{
			return $this->deleteEntry($entries);
		}
		else
		{
			return false;
		}
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSaveEntry' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveEntry(Event $event)
	{
		$this->raiseEvent('onBeforeSaveEntry', $event);
	}

	/**
	 * Fires an 'onSaveEntry' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveEntry(Event $event)
	{
		$this->raiseEvent('onSaveEntry', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteEntry' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeleteEntry(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteEntry', $event);
	}

	/**
	 * Fires an 'onDeleteEntry' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeleteEntry(Event $event)
	{
		$this->raiseEvent('onDeleteEntry', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Checks if an entry was submitted with a new parent entry selected.
	 *
	 * @param EntryModel $entry
	 *
	 * @return bool
	 */
	private function _checkForNewParent(EntryModel $entry)
	{
		// Make sure this is a Structure section
		if ($entry->getSection()->type != SectionType::Structure)
		{
			return false;
		}

		// Is it a brand new entry?
		if (!$entry->id)
		{
			return true;
		}

		// Was a parentId actually submitted?
		if ($entry->parentId === null)
		{
			return false;
		}

		// Is it set to the top level now, but it hadn't been before?
		if ($entry->parentId === '' && $entry->level != 1)
		{
			return true;
		}

		// Is it set to be under a parent now, but didn't have one before?
		if ($entry->parentId !== '' && $entry->level == 1)
		{
			return true;
		}

		// Is the parentId set to a different entry ID than its previous parent?
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->ancestorOf = $entry;
		$criteria->ancestorDist = 1;
		$criteria->status = null;
		$criteria->locale = $entry->locale;
		$criteria->localeEnabled = null;

		$oldParent = $criteria->first();
		$oldParentId = ($oldParent ? $oldParent->id : '');

		if ($entry->parentId != $oldParentId)
		{
			return true;
		}

		// Must be set to the same one then
		return false;
	}
}
