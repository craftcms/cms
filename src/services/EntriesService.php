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

			$entryRecordClass = __NAMESPACE__.'\\StructuredEntryRecord';
		}
		else
		{
			$entryRecordClass = __NAMESPACE__.'\\EntryRecord';
		}

		// Entry data
		if (!$isNewEntry)
		{
			$entryRecord = $entryRecordClass::model()->with('element')->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}

			$elementRecord = $entryRecord->element;
		}
		else
		{
			$entryRecord = new $entryRecordClass();

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

		$fieldLayout = $entryType->getFieldLayout();

		craft()->content->prepElementContentForSave($entry, $fieldLayout);

		if (!craft()->content->validateElementContent($entry, $fieldLayout))
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
				$entryLocaleRecord = EntryLocaleRecord::model()->findByAttributes(array(
					'entryId' => $entry->id,
					'locale'  => $entry->locale
				));

				$elementLocaleRecord = ElementLocaleRecord::model()->findByAttributes(array(
					'elementId' => $entry->id,
					'locale'    => $entry->locale
				));
			}

			if (empty($entryLocaleRecord))
			{
				$entryLocaleRecord = new EntryLocaleRecord();
				$entryLocaleRecord->sectionId = $entry->sectionId;
				$entryLocaleRecord->locale    = $entry->locale;
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

			$entry->slug = $this->_cleanSlug($entry->slug);

			$entryLocaleRecord->slug = null;
			$elementLocaleRecord->uri = null;

			// Set a slug that ensures the URI will be unique across all elements
			if ($section->hasUrls && $entry->slug)
			{
				// Get the appropriate URL Format attribute based on the section type and entry depth
				if ($section->type == SectionType::Structure && (
					($hasNewParent && $entry->parentId) ||
					(!$hasNewParent && $entry->depth != 1)
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

			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Save the element record first
				$elementRecord->save(false);

				// Save the element id on the entry model, in case {id} is in the url format
				$entry->id = $elementRecord->id;

				// Set a unique slug and URI
				$this->_setUniqueSlugAndUri($entry, $urlFormat, $entryLocaleRecord, $elementLocaleRecord, $isNewEntry);

				// Validate them
				$entryLocaleRecord->validate();
				$entry->addErrors($entryLocaleRecord->getErrors());

				$elementLocaleRecord->validate();
				$entry->addErrors($elementLocaleRecord->getErrors());

				if (!$entry->hasErrors())
				{

					// Now that we have an element ID, save it on the other stuff
					if ($isNewEntry)
					{
						$entryRecord->id = $entry->id;
					}

					// Has the parent changed?
					if ($hasNewParent)
					{
						if (!$entry->parentId)
						{
							$parentEntryRecord = StructuredEntryRecord::model()->roots()->findByAttributes(array(
								'sectionId' => $section->id
							));
						}
						else
						{
							$parentEntryRecord = StructuredEntryRecord::model()->findById($entry->parentId);
						}

						if ($isNewEntry)
						{
							$entryRecord->appendTo($parentEntryRecord);
						}
						else
						{
							$entryRecord->moveAsLast($parentEntryRecord);
						}

						$entryRecord->detachBehavior('nestedSet');

						$entry->root  = $entryRecord->root;
						$entry->lft   = $entryRecord->lft;
						$entry->rgt   = $entryRecord->rgt;
						$entry->depth = $entryRecord->depth;
					}

					// Save everything!
					$entryRecord->save(false);

					$entry->getContent()->elementId = $entry->id;
					craft()->content->saveContent($entry->getContent());

					if ($section->type != SectionType::Single)
					{
						// Save the locale records
						$entryLocaleRecord->entryId = $entry->id;
						$elementLocaleRecord->elementId = $entry->id;

						$entryLocaleRecord->save(false);
						$elementLocaleRecord->save(false);

						if (!$isNewEntry && $section->hasUrls)
						{
							if (craft()->hasPackage(CraftPackage::Localize))
							{
								// Update the other locale records too, just to be safe
								// (who knows what the URL Format is using that could have just changed)
								foreach ($sectionLocales as $sectionLocale)
								{
									if ($sectionLocale->locale == $entry->locale)
									{
										continue;
									}

									$this->updateEntrySlugAndUri($entry->id, $sectionLocale->locale, $sectionLocale->$urlFormatAttribute);
								}
							}

							if ($section->type == SectionType::Structure)
							{
								// Update the entry's descendants, who may be using this entry's URI in their own URIs
								$this->_updateDescendantSlugsAndUris($entry->id, false);
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

					// Perform some post-save operations
					craft()->content->postSaveOperations($entry, $entry->getContent());

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

					$entry->id = null;

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
	 * Appends an entry to another.
	 *
	 * @param EntryModel $entry
	 * @param EntryModel|null $parentEntry
	 * @param bool $prepend
	 * @return bool
	 */
	public function moveEntryUnder(EntryModel $entry, EntryModel $parentEntry = null, $prepend = false)
	{
		craft()->requirePackage(CraftPackage::PublishPro);

		$entryRecord = StructuredEntryRecord::model()->populateRecord($entry->getAttributes());

		if ($parentEntry)
		{
			// Make sure they're in the same section
			if ($entry->sectionId != $parentEntry->sectionId)
			{
				throw new Exception(Craft::t('That move isn’t possible.'));
			}

			$parentEntryRecord = StructuredEntryRecord::model()->populateRecord($parentEntry->getAttributes());
		}
		else
		{
			// Parent is the root node, then
			$parentEntryRecord = StructuredEntryRecord::model()->roots()->findByAttributes(array(
				'sectionId' => $entryRecord->sectionId
			));

			if (!$parentEntryRecord)
			{
				throw new Exception('There’s no root node in this section.');
			}
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			if ($prepend)
			{
				$success = $entryRecord->moveAsFirst($parentEntryRecord);
			}
			else
			{
				$success = $entryRecord->moveAsLast($parentEntryRecord);
			}

			if ($success)
			{
				$this->_updateDescendantSlugsAndUris($entry->id);
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return $success;
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

	/**
	 * Moves an entry after another.
	 * @param EntryModel $entry
	 * @param EntryModel $prevEntry
	 * @return bool
	 */
	public function moveEntryAfter($entry, $prevEntry)
	{
		$entryRecord = StructuredEntryRecord::model()->findById($entry->id);

		if (!$entryRecord)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
		}

		$prevEntryRecord = StructuredEntryRecord::model()->findById($prevEntry->id);

		if (!$prevEntryRecord)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $prevEntry->id)));
		}

		// Make sure they're in the same section
		if ($entryRecord->sectionId != $prevEntryRecord->sectionId)
		{
			throw new Exception(Craft::t('That move isn’t possible.'));
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$success = $entryRecord->moveAfter($prevEntryRecord);

			if ($success)
			{
				$this->_updateDescendantSlugsAndUris($entry->id);
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return $success;
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

	/**
	 * Updates an entry's slug and URI for a given locale with a given URL format.
	 *
	 * @param int $entryId
	 * @param string $localeId
	 * @param string|null $urlFormat
	 */
	public function updateEntrySlugAndUri($entryId, $localeId, $urlFormat)
	{
		$entryLocaleRecord = EntryLocaleRecord::model()->findByAttributes(array(
			'entryId' => $entryId,
			'locale'  => $localeId
		));

		$elementLocaleRecord = ElementLocaleRecord::model()->findByAttributes(array(
			'elementId' => $entryId,
			'locale'    => $localeId
		));

		if (!$entryLocaleRecord || !$elementLocaleRecord)
		{
			// Entry hasn't been saved in this locale yet.
			return;
		}

		// Get the actual entry model
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->id = $entryId;
		$criteria->locale = $localeId;
		$criteria->status = null;
		$entry = $criteria->first();

		if (!$entry)
		{
			// WTF?
			return;
		}

		$oldSlug = $entryLocaleRecord->slug;
		$oldUri = $elementLocaleRecord->uri;

		$this->_setUniqueSlugAndUri($entry, $urlFormat, $entryLocaleRecord, $elementLocaleRecord, false);

		if ($entryLocaleRecord->slug != $oldSlug)
		{
			$entryLocaleRecord->save(false);
		}

		if ($elementLocaleRecord->uri != $oldUri)
		{
			$elementLocaleRecord->save(false);
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
		if ($entry->parentId === '0' && $entry->depth != 1)
		{
			return true;
		}

		// Is it set to be under a parent now, but didn't have one before?
		if ($entry->parentId !== '0' && $entry->depth == 1)
		{
			return true;
		}

		// Is the parentId set to a different entry ID than its previous parent?
		$oldParent = $entry->getParent();
		$oldParentId = ($oldParent ? $oldParent->id : '0');

		if ($entry->parentId != $oldParentId)
		{
			return true;
		}

		// Must be set to the same one then
		return false;
	}

	/**
	 * Cleans an entry slug.
	 *
	 * @access private
	 * @param string $slug
	 * @return string
	 */
	private function _cleanSlug($slug)
	{
		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/u', '', $slug);

		// Remove inner-word punctuation.
		$slug = preg_replace('/[\'"‘’“”]/u', '', $slug);

		// Make it lowercase
		$slug = mb_strtolower($slug, 'UTF-8');

		// Get the "words".  Split on anything that is not a unicode letter or number.
		// Periods are OK too.
		preg_match_all('/[\p{L}\p{N}\.]+/u', $slug, $words);
		$words = ArrayHelper::filterEmptyStringsFromArray($words[0]);
		$slug = implode('-', $words);

		return $slug;
	}

	/**
	 * Sets a unique slug and URI on entry locale rows.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @param string $urlFormat;
	 * @param EntryLocaleRecord $entryLocaleRecord
	 * @param ElementLocaleRecord $elementLocaleRecord
	 * @param bool $isNewEntry
	 */
	private function _setUniqueSlugAndUri(EntryModel $entry, $urlFormat, EntryLocaleRecord $entryLocaleRecord, ElementLocaleRecord $elementLocaleRecord, $isNewEntry)
	{
		if (!$entry->slug)
		{
			// Just make sure it doesn't have a URI.
			if ($elementLocaleRecord->uri)
			{
				$elementLocaleRecord->uri = null;
			}

			return;
		}

		// Find a unique slug for this section/locale
		$uniqueSlugConditions = array('and',
			'sectionId = :sectionId',
			'locale = :locale',
			'slug = :slug'
		);

		$uniqueSlugParams = array(
			':sectionId' => $entry->sectionId,
			':locale'    => $entryLocaleRecord->locale
		);

		if (!$isNewEntry)
		{
			$uniqueSlugConditions[] = 'entryId != :entryId';
			$uniqueSlugParams[':entryId'] = $entry->id;
		}

		if ($urlFormat)
		{
			$uniqueUriConditions = array('and',
				'locale = :locale',
				'uri = :uri'
			);

			$uniqueUriParams = array(
				':locale' => $entryLocaleRecord->locale
			);

			if (!$isNewEntry)
			{
				$uniqueUriConditions[] = 'elementId != :elementId';
				$uniqueUriParams[':elementId'] = $entry->id;
			}
		}

		for ($i = 0; $i < 100; $i++)
		{
			$testSlug = $entry->slug;

			if ($i > 0)
			{
				$testSlug .= '-'.$i;
			}

			$uniqueSlugParams[':slug'] = $testSlug;

			$totalEntries = craft()->db->createCommand()
				->select('count(id)')
				->from('entries_i18n')
				->where($uniqueSlugConditions, $uniqueSlugParams)
				->queryScalar();

			if ($totalEntries == 0)
			{
				if ($urlFormat)
				{
					// Great, the slug is unique. Is the URI?
					$originalSlug = $entry->slug;
					$entry->slug = $testSlug;

					$testUri = craft()->templates->renderObjectTemplate($urlFormat, $entry);

					// Make sure we're not over our max length.
					if (strlen($testUri) > 255)
					{
						// See how much over we are.
						$overage = strlen($testUri) - 255;

						// Do we have anything left to chop off?
						if (strlen($overage) > strlen($entry->slug) - strlen('-'.$i))
						{
							// Chop off the overage amount from the slug
							$testSlug = $entry->slug;
							$testSlug = substr($testSlug, 0, strlen($testSlug) - $overage);

							// Update the slug
							$entry->slug = $testSlug;

							// Let's try this again.
							$i -= 1;
							continue;

						}
						else
						{
							// We're screwed, blow things up.
							throw new Exception(Craft::t('The maximum length of a URI is 255 characters.'));
						}
					}

					$uniqueUriParams[':uri'] = $testUri;

					$totalElements = craft()->db->createCommand()
						->select('count(id)')
						->from('elements_i18n')
						->where($uniqueUriConditions, $uniqueUriParams)
						->queryScalar();

					if ($totalElements ==  0)
					{
						// OMG!
						$entryLocaleRecord->slug = $testSlug;
						$elementLocaleRecord->uri = $testUri;
						return;
					}
					else
					{
						$entry->slug = $originalSlug;
					}
				}
				else
				{
					$entry->slug = $testSlug;
					$entryLocaleRecord->slug = $testSlug;
					$elementLocaleRecord->uri = null;
					return;
				}
			}
		}
	}

	/**
	 * Updates an entry’s descendants’ slugs and URIs.
	 *
	 * @access private
	 * @param int $parentEntryId
	 * @param bool $updateParent
	 */
	private function _updateDescendantSlugsAndUris($parentEntryId, $updateParent = true)
	{
		$parentEntryRecord = StructuredEntryRecord::model()->with('section', 'section.locales')->findById($parentEntryId);

		if (!$parentEntryRecord)
		{
			throw new Exception(Craft::t('No entry exists with the ID “{id}”', array('id' => $parentEntryId)));
		}

		$sectionRecord = $parentEntryRecord->section;
		$descendantEntryRecords = $parentEntryRecord->descendants()->with('element')->findAll();

		if ($updateParent)
		{
			array_unshift($descendantEntryRecords, $parentEntryRecord);
		}

		foreach ($descendantEntryRecords as $descendantEntryRecord)
		{
			foreach ($sectionRecord->locales as $sectionLocaleRecord)
			{
				if ($sectionRecord->hasUrls)
				{
					if ($descendantEntryRecord->depth == 1)
					{
						$urlFormat = $sectionLocaleRecord->urlFormat;
					}
					else
					{
						$urlFormat = $sectionLocaleRecord->nestedUrlFormat;
					}
				}
				else
				{
					$urlFormat = null;
				}

				$this->updateEntrySlugAndUri($descendantEntryRecord->id, $sectionLocaleRecord->locale, $urlFormat);
			}
		}
	}
}
