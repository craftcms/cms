<?php
namespace Craft;

/**
 *
 */
class EntriesService extends BaseApplicationComponent
{
	/**
	 * Returns tags by a given entry ID.
	 *
	 * @param $entryId
	 * @return array
	 */
	public function getTagsByEntryId($entryId)
	{
		$tags = array();

		$entryRecord = EntryRecord::model()->findById($entryId);
		$entryTagRecords = $this->_getTagsForEntry($entryRecord);

		foreach ($entryTagRecords as $record)
		{
			$tags[] = $record->name;
		}

		return $tags;
	}

	/**
	 * Saves an entry.
	 *
	 * @param EntryModel $entry
	 * @throws Exception
	 * @return bool
	 */
	public function saveEntry(EntryModel $entry)
	{
		$isNewEntry = !$entry->id;

		// Entry data
		if (!$isNewEntry)
		{
			$entryRecord = EntryRecord::model()->with('element', 'entryTagEntries')->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}

			$elementRecord = $entryRecord->element;

			// if entry->sectionId is null and there is an entryRecord sectionId, we assume this is a front-end edit.
			if ($entry->sectionId === null && $entryRecord->sectionId)
			{
				$entry->sectionId = $entryRecord->sectionId;
			}
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
		$entryRecord->authorId   = $entry->authorId;
		$entryRecord->postDate   = $entry->postDate;
		$entryRecord->expiryDate = $entry->expiryDate;

		if ($entry->enabled && !$entryRecord->postDate)
		{
			// Default the post date to the current date/time
			$entryRecord->postDate = $entry->postDate = DateTimeHelper::currentUTCDateTime();
		}

		$entryRecord->validate();
		$entry->addErrors($entryRecord->getErrors());

		$elementRecord->enabled = $entry->enabled;
		$elementRecord->validate();
		$entry->addErrors($elementRecord->getErrors());

		// Entry locale data
		if ($entry->id)
		{
			$entryLocaleRecord = EntryLocaleRecord::model()->findByAttributes(array(
				'entryId' => $entry->id,
				'locale'  => $entry->locale
			));

			// if entry->slug is null and there is an entryLocaleRecord slug, we assume this is a front-end edit.
			if ($entry->slug === null && $entryLocaleRecord->slug)
			{
				$entry->slug = $entryLocaleRecord->slug;
			}
		}

		if (empty($entryLocaleRecord))
		{
			$entryLocaleRecord = new EntryLocaleRecord();
			$entryLocaleRecord->sectionId = $entry->sectionId;
			$entryLocaleRecord->locale    = $entry->locale;
		}

		$entryLocaleRecord->title = $entry->title;

		if ($entryLocaleRecord->isNewRecord() || $entry->slug != $entryLocaleRecord->slug)
		{
			$this->_generateEntrySlug($entry);
			$entryLocaleRecord->slug = $entry->slug;
		}

		$entryLocaleRecord->validate();
		$entry->addErrors($entryLocaleRecord->getErrors());

		// Element locale data
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

		if ($section->hasUrls && $entry->enabled)
		{
			// Make sure the section's URL format is valid. This shouldn't be possible due to section validation,
			// but it's not enforced by the DB, so anything is possible.
			$urlFormat = $sectionLocales[$entry->locale]->urlFormat;

			if (!$urlFormat || strpos($urlFormat, '{slug}') === false)
			{
				throw new Exception(Craft::t('The section “{section}” doesn’t have a valid URL Format.', array(
					'section' => Craft::t($section->name)
				)));
			}

			$elementLocaleRecord->uri = craft()->templates->renderObjectTemplate($urlFormat, $entry);
		}
		else
		{
			$elementLocaleRecord->uri = null;
		}

		$elementLocaleRecord->validate();
		$entry->addErrors($elementLocaleRecord->getErrors());

		// Entry content
		$fieldLayout = $section->getFieldLayout();
		$content = craft()->content->prepElementContentForSave($entry, $fieldLayout);
		$content->validate();
		$entry->addErrors($content->getErrors());

		// Tags
		$entryTagRecords = $this->_processTags($entry, $entryRecord);
		$tagErrors = $this->_validateEntryTagRecords($entryTagRecords);
		$entry->addErrors($tagErrors);

		if (!$entry->hasErrors())
		{
			// Save the element record first
			$elementRecord->save(false);

			// Now that we have an element ID, save it on the other stuff
			if (!$entry->id)
			{
				$entry->id = $elementRecord->id;
				$entryRecord->id = $entry->id;
			}

			$entryRecord->save(false);

			$entryLocaleRecord->entryId = $entry->id;
			$elementLocaleRecord->elementId = $entry->id;
			$content->elementId = $entry->id;

			// Save the other records
			$entryLocaleRecord->save(false);
			$elementLocaleRecord->save(false);
			craft()->content->saveContent($content, false);

			// Update the search index
			craft()->search->indexElementAttributes($entry, $entry->locale);

			// If we have any tags to process
			if (!empty($entryTagRecords))
			{
				// Create any of the new tag records first.
				if (isset($entryTagRecords['new']))
				{
					foreach ($entryTagRecords['new'] as $newEntryTagRecord)
					{
						$newEntryTagRecord->save(false);
					}
				}

				// Add any tags to the entry.
				if (isset($entryTagRecords['add']))
				{
					foreach ($entryTagRecords['add'] as $addEntryTagRecord)
					{
						$entryTagEntryRecord = new EntryTagEntryRecord();
						$entryTagEntryRecord->tagId = $addEntryTagRecord->id;
						$entryTagEntryRecord->entryId = $elementRecord->id;
						$entryTagEntryRecord->save(false);

						$this->_updateTagCount($addEntryTagRecord);
					}
				}

				// Process any tags that need to be removed from the entry.
				if (isset($entryTagRecords['delete']))
				{
					foreach ($entryTagRecords['delete'] as $deleteEntryTagRecord)
					{
						EntryTagEntryRecord::model()->deleteAllByAttributes(array(
							'tagId'   => $deleteEntryTagRecord->id,
							'entryId' => $elementRecord->id
						));

						$this->_updateTagCount($deleteEntryTagRecord);
					}
				}
			}

			// Save a new version
			if (Craft::hasPackage(CraftPackage::PublishPro))
			{
				craft()->entryRevisions->saveVersion($entry);
			}

			// Perform some post-save operations
			craft()->content->postSaveOperations($entry, $content);

			// Fire an 'onSaveEntry' event
			$this->onSaveEntry(new Event($this, array(
				'entry'      => $entry,
				'isNewEntry' => $isNewEntry
			)));

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Fires an 'onSaveEntry' event.
	 *
	 * @param Event $event
	 */
	public function onSaveEntry(Event $event)
	{
		$this->raiseEvent('onSaveEntry', $event);
	}

	// Private methods
	// ===============

	/**
	 * Generates an entry slug based on its title.
	 *
	 * @access private
	 * @param EntryModel $entry
	 */
	private function _generateEntrySlug(EntryModel $entry)
	{
		$slug = ($entry->slug ? $entry->slug : $entry->title);

		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/', '', $slug);

		// Remove apostrophes
		$slug = str_replace(array('\'', '’'), array('', ''), $slug);

		// Make it lowercase
		$slug = strtolower($slug);

		// Convert extended ASCII characters to basic ASCII
		$slug = StringHelper::asciiString($slug);

		// Slug must start and end with alphanumeric characters
		$slug = preg_replace('/^[^a-z0-9]+/', '', $slug);
		$slug = preg_replace('/[^a-z0-9]+$/', '', $slug);

		// Get the "words"
		$words = preg_split('/[^a-z0-9]+/', $slug);
		$words = ArrayHelper::filterEmptyStringsFromArray($words);
		$slug = implode('-', $words);

		if ($slug)
		{
			// Make it unique
			$conditions = array('and', 'sectionId = :sectionId', 'locale = :locale', 'slug = :slug');
			$params = array(':sectionId' => $entry->sectionId, ':locale' => $entry->locale);

			if ($entry->id)
			{
				$conditions[] = 'id != :entryId';
				$params[':entryId'] = $entry->id;
			}

			for ($i = 0; true; $i++)
			{
				$testSlug = $slug.($i != 0 ? "-{$i}" : '');
				$params[':slug'] = $testSlug;

				$totalEntries = craft()->db->createCommand()
					->select('count(id)')
					->from('entries_i18n')
					->where($conditions, $params)
					->queryScalar();

				if ($totalEntries == 0)
				{
					break;
				}
			}

			$entry->slug = $testSlug;
		}
		else
		{
			$entry->slug = '';
		}
	}

	/**
	 * Keeps the given $entryTagRecord->count column up-to-date with the number of entries using that tag.
	 *
	 * @param EntryTagRecord $entryTagRecord
	 */
	private function _updateTagCount(EntryTagRecord $entryTagRecord)
	{
		$criteria = new \CDbCriteria();
		$criteria->addCondition('tagId =:tagId');
		$criteria->params[':tagId'] = $entryTagRecord->id;
		$tagCount = EntryTagEntryRecord::model()->count($criteria);

		// If the count is zero, let's delete the entryTagRecord.
		if ($tagCount == 0)
		{
			$entryTagRecord->delete();
		}
		else
		{
			$entryTagRecord->count = $tagCount;
			$entryTagRecord->save(false);
		}
	}

	/**
	 * Checks to see if there are any tag validation errors.  If so, returns them.
	 *
	 * @param $entryTagRecords
	 * @return array
	 */
	private function _validateEntryTagRecords($entryTagRecords)
	{
		$errors = array();

		foreach ($entryTagRecords as $entryTagRecordActions)
		{
			foreach ($entryTagRecordActions as $entryTagRecord)
			{
				if (!$entryTagRecord->validate())
				{
					$errors[] = $entryTagRecord->getErrors();
				}
			}
		}

		return $errors;
	}

	/**
	 * Processes any tags on the EntryModel for the given EntryRecord.  Will generate a list of tags that need to be
	 * added, updated or deleted for an entry.
	 *
	 * @param EntryModel  $entry
	 * @param EntryRecord $entryRecord
	 * @return array
	 */
	private function _processTags(EntryModel $entry, EntryRecord $entryRecord)
	{
		$entryTagRecords = array();

		// Get the entries' current EntryTags
		$currentEntryTagRecords = $this->_getTagsForEntry($entryRecord);

		// See if any tags have even changed for this entry.
		if (count($currentEntryTagRecords) == count($entry->tags))
		{
			$identical = true;

			foreach ($currentEntryTagRecords as $currentEntryTagRecord)
			{
				if (!preg_grep("/{$currentEntryTagRecord->name}/i", $entry->tags))
				{
					// Something is different.
					$identical = false;
					break;
				}
			}

			if ($identical)
			{
				// Identical, so just return the empty array.
				return $entryTagRecords;
			}
		}

		// Process the new entry tags.
		foreach ($entry->tags as $newEntryTag)
		{
			foreach ($currentEntryTagRecords as $currentEntryTagRecord)
			{
				// The current entry already has this tag assigned to it... skip.
				if (strtolower($currentEntryTagRecord->name) == strtolower($newEntryTag))
				{
					// Try the next $newEntryTag
					continue 2;
				}
			}

			// If we make it here, then we know the tag is new for this entry because it doesn't exist in $currentEntryTagRecords
			// Make sure the tag exists at all, if not create the record.
			if (($entryTagRecord = $this->_getEntryTagRecordByName($newEntryTag)) == null)
			{
				$entryTagRecord = new EntryTagRecord();
				$entryTagRecord->name = $newEntryTag;
				$entryTagRecord->count = 1;

				// Keep track of the new tag records.
				$entryTagRecords['new'][] = $entryTagRecord;
			}

			// Keep track of the tags we'll need to add to the entry.
			$entryTagRecords['add'][] = $entryTagRecord;
		}

		// Now check for deleted tags from the entry.
		foreach ($currentEntryTagRecords as $currentEntryTagRecord)
		{
			foreach ($entry->tags as $newEntryTag)
			{
				if (strtolower($currentEntryTagRecord->name) == strtolower($newEntryTag))
				{
					// Try the next $currentEntryTagRecord
					continue 2;
				}
			}

			// If we made it here, then we know the tag was removed from the entry.
			$entryTagRecords['delete'][] = $currentEntryTagRecord;
		}

		return $entryTagRecords;
	}

	/**
	 * Given an entry, will return an array of EntryTagRecords associated with the entry.
	 *
	 * @param EntryRecord $entryRecord
	 * @return array
	 */
	private function _getTagsForEntry(EntryRecord $entryRecord)
	{
		$currentEntryTagRecords = array();
		$entryTagEntries = $entryRecord->entryTagEntries;

		foreach ($entryTagEntries as $entryTagEntry)
		{
			if (($currentEntryTagRecord = $this->_getEntryTagRecordById($entryTagEntry->tagId)) !== null)
			{
				$currentEntryTagRecords[] = $currentEntryTagRecord;
			}
		}

		return $currentEntryTagRecords;
	}

	/**
	 * Returns an EntryTagRecord with the given tag name.
	 *
	 * @param $tagName
	 * @return EntryTagRecord
	 */
	private function _getEntryTagRecordByName($tagName)
	{
		$entryTagRecord = EntryTagRecord::model()->findByAttributes(
			array('name' => $tagName)
		);

		return $entryTagRecord;
	}

	/**
	 * Returns an EntryTagRecord with the given ID.
	 *
	 * @param $id
	 * @return EntryTagRecord
	 */
	private function _getEntryTagRecordById($id)
	{
		$entryTagRecord = EntryTagRecord::model()->findByPk($id);
		return $entryTagRecord;
	}
}
