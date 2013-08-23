<?php
namespace Craft;

/**
 *
 */
class EntriesService extends BaseApplicationComponent
{
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
			$entryRecord = EntryRecord::model()->with('element')->findById($entry->id);

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

			// If entry->slug is null and there is an entryLocaleRecord slug, we assume this is a front-end edit.
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

			if (!$urlFormat || mb_strpos($urlFormat, '{slug}') === false)
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
		$entryType = $entry->getType();

		if (!$entryType)
		{
			throw new Exception(Craft::t('No entry types are available for this entry.'));
		}

		// Set the typeId attribute on the model in case it hasn't been set
		$entry->typeId = $entryRecord->typeId = $entryType->id;

		$fieldLayout = $entryType->getFieldLayout();
		$content = craft()->content->prepElementContentForSave($entry, $fieldLayout);
		$content->validate();
		$entry->addErrors($content->getErrors());

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
		$slug = ($entry->slug ? $entry->slug : $entry->getTitle());

		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/', '', $slug);

		// Remove apostrophes
		$slug = str_replace(array('\'', '’'), array('', ''), $slug);

		// Make it lowercase
		$slug = mb_strtolower($slug);

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
}
