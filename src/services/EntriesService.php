<?php
namespace Craft;

/**
 *
 */
class EntriesService extends BaseApplicationComponent
{
	/**
	 * Returns an entry by its ID.
	 *
	 * @param int $entryId
	 * @return EntryModel|null
	 */
	public function getEntryById($entryId)
	{
		return craft()->elements->getElementById($entryId, ElementType::Entry);
	}

	/**
	 * Saves an entry.
	 *
	 * @param EntryModel $entry
	 * @throws \Exception
	 * @return bool
	 */
	public function saveEntry(EntryModel $entry)
	{
		$isNewEntry = !$entry->id;

		$hasNewParent = $this->_checkForNewParent($entry);

		if ($hasNewParent)
		{
			if ($entry->parentId)
			{
				$parentEntry = $this->getEntryById($entry->parentId);

				if (!$parentEntry)
				{
					throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->parentId)));
				}
			}
			else
			{
				$parentEntry = null;
			}

			$entry->setParent($parentEntry);
		}

		// Entry data
		if (!$isNewEntry)
		{
			$entryRecord = EntryRecord::model()->with('element')->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}

			$elementRecord = $entryRecord->element;
		}
		else
		{
			$entryRecord = new EntryRecord();

			$elementRecord = new ElementRecord();
			$elementRecord->type = ElementType::Entry;
		}

		$section = craft()->sections->getSectionById($entry->sectionId);

		if (!$section)
		{
			throw new Exception(Craft::t('No section exists with the ID “{id}”', array('id' => $entry->sectionId)));
		}

		$sectionLocales = $section->getLocales();

		if (!isset($sectionLocales[$entry->locale]))
		{
			throw new Exception(Craft::t('The section “{section}” is not enabled for the locale {locale}', array('section' => $section->name, 'locale' => $entry->locale)));
		}

		$entryRecord->sectionId  = $entry->sectionId;
		$entryRecord->postDate   = $entry->postDate;

		if ($section->type == SectionType::Single)
		{
			$entryRecord->authorId   = $entry->authorId = null;
			$entryRecord->expiryDate = $entry->expiryDate = null;

			$elementRecord->enabled  = $entry->enabled = true;
		}
		else
		{
			$entryRecord->authorId   = $entry->authorId;
			$entryRecord->postDate   = $entry->postDate;
			$entryRecord->expiryDate = $entry->expiryDate;

			$elementRecord->enabled  = $entry->enabled;
		}

		if ($entry->enabled && !$entryRecord->postDate)
		{
			// Default the post date to the current date/time
			$entryRecord->postDate = $entry->postDate = DateTimeHelper::currentUTCDateTime();
		}

		$entryRecord->validate();
		$elementRecord->validate();

		$entry->addErrors($entryRecord->getErrors());
		$entry->addErrors($elementRecord->getErrors());

		// Entry content
		$entryType = $entry->getType();

		if (!$entryType)
		{
			throw new Exception(Craft::t('No entry types are available for this entry.'));
		}

		// Set the typeId attribute on the model in case it hasn't been set
		$entry->typeId = $entryRecord->typeId = $entryType->id;

		if (!craft()->content->validateContent($entry))
		{
			$entry->addErrors($entry->getContent()->getErrors());
		}

		// Only worry about entry and element locale stuff if it's a channel or structure section
		// since singles already have all of the locale records they'll ever need
		// and that data never gets changed, outside of when the section is edited.

		if ($section->type != SectionType::Single)
		{
			// Get the entry/element locale records
			if ($entry->id)
			{
				$elementLocaleRecord = ElementLocaleRecord::model()->findByAttributes(array(
					'elementId' => $entry->id,
					'locale'    => $entry->locale
				));
			}

			if (empty($elementLocaleRecord))
			{
				$elementLocaleRecord = new ElementLocaleRecord();
				$elementLocaleRecord->locale = $entry->locale;
			}

			if (!$entry->slug)
			{
				// Use the title as a starting point
				$entry->slug = $entry->title;
			}

			ElementHelper::setValidSlug($entry);

			$elementLocaleRecord->slug = null;
			$elementLocaleRecord->uri = null;

			// Set a slug that ensures the URI will be unique across all elements
			if ($section->hasUrls && $entry->slug)
			{
				// Get the appropriate URL Format attribute based on the section type and entry level
				if ($section->type == SectionType::Structure && (
					($hasNewParent && $entry->parentId) ||
					(!$hasNewParent && $entry->level != 1)
				))
				{
					$urlFormatAttribute = 'nestedUrlFormat';
				}
				else
				{
					$urlFormatAttribute = 'urlFormat';
				}

				$urlFormat = $sectionLocales[$entry->locale]->$urlFormatAttribute;

				// Make sure the section's URL format is valid. This shouldn't be possible due to section validation,
				// but it's not enforced by the DB, so anything is possible.
				if (!$urlFormat || mb_strpos($urlFormat, '{slug}') === false)
				{
					throw new Exception(Craft::t('The section “{section}” doesn’t have a valid URL Format.', array(
						'section' => Craft::t($section->name)
					)));
				}
			}
			else
			{
				$urlFormat = null;
			}
		}

		if (!$entry->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Save the element record first
				$elementRecord->save(false);

				if ($isNewEntry)
				{
					// Save the element id on the entry model, in case {id} is in the url format
					$entry->id = $elementRecord->id;
				}

				if ($section->type != SectionType::Single)
				{
					// Set a unique slug and URI
					ElementHelper::setUniqueUri($entry, $urlFormat);
					$elementLocaleRecord->slugIsRequired = true;
					$elementLocaleRecord->slug = $entry->slug;
					$elementLocaleRecord->uri  = $entry->uri;
					$elementLocaleRecord->validate();
					$entry->addErrors($elementLocaleRecord->getErrors());
				}

				if (!$entry->hasErrors())
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewEntry)
					{
						$entryRecord->id = $entry->id;
					}

					// Save the actual entry row
					$entryRecord->save(false);

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

					// Save the content
					$entry->getContent()->elementId = $entry->id;
					craft()->content->saveContent($entry, false);

					if ($section->type != SectionType::Single)
					{
						// Save the locale record
						$elementLocaleRecord->elementId = $entry->id;
						$elementLocaleRecord->save(false);

						if (!$isNewEntry && $section->hasUrls)
						{
							craft()->elements->updateElementSlugAndUriInOtherLocales($entry);

							if ($section->type == SectionType::Structure)
							{
								// Update the entry's descendants, who may be using this entry's URI in their own URIs
								craft()->elements->updateDescendantSlugsAndUris($entry);
							}
						}
					}

					// Update the search index
					craft()->search->indexElementAttributes($entry, $entry->locale);

					// Save a new version
					if (craft()->hasPackage(CraftPackage::PublishPro))
					{
						craft()->entryRevisions->saveVersion($entry);
					}

					// Fire an 'onSaveEntry' event
					$this->onSaveEntry(new Event($this, array(
						'entry'      => $entry,
						'isNewEntry' => $isNewEntry
					)));

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
				}
				else
				{
					if ($transaction !== null)
					{
						$transaction->rollback();
					}

					if ($isNewEntry)
					{
						$entry->id = null;
					}

					return false;
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
		else
		{
			return false;
		}
	}

	/**
	 * Deletes an entry(s).
	 *
	 * @param EntryModel|array $entries
	 * @return bool
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
				$this->onBeforeDeleteEntry(new Event($this, array(
					'entry' => $entry
				)));

				$entryIds[] = $entry->id;
			}

			// Delete 'em
			$success = craft()->elements->deleteElementById($entryIds);

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
	 * @param int|array $entryId
	 * @return bool
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
	// ======

	/**
	 * Fires an 'onSaveEntry' event.
	 *
	 * @param Event $event
	 */
	public function onSaveEntry(Event $event)
	{
		$this->raiseEvent('onSaveEntry', $event);
	}

	/**
	 * Fires an 'onBeforeDeleteEntry' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeDeleteEntry(Event $event)
	{
		$this->raiseEvent('onBeforeDeleteEntry', $event);
	}

	/**
	 * Fires an 'onDeleteEntry' event.
	 *
	 * @param Event $event
	 */
	public function onDeleteEntry(Event $event)
	{
		$this->raiseEvent('onDeleteEntry', $event);
	}

	// Private methods
	// ===============

	/**
	 * Checks if an entry was submitted with a new parent entry selected.
	 *
	 * @access private
	 * @param EntryModel $entry
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
		if ($entry->parentId === '0' && $entry->level != 1)
		{
			return true;
		}

		// Is it set to be under a parent now, but didn't have one before?
		if ($entry->parentId !== '0' && $entry->level == 1)
		{
			return true;
		}

		// Is the parentId set to a different entry ID than its previous parent?
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->ancestorOf = $entry;
		$criteria->ancestorDist = 1;
		$criteria->status = null;

		$oldParent = $criteria->first();
		$oldParentId = ($oldParent ? $oldParent->id : '0');

		if ($entry->parentId != $oldParentId)
		{
			return true;
		}

		// Must be set to the same one then
		return false;
	}
}
