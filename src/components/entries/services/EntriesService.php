<?php
namespace Blocks;

/**
 *
 */
class EntriesService extends BaseApplicationComponent
{
	/**
	 * Returns an entry criteria model for a given entry type.
	 *
	 * @param string $entryType
	 * @return EntryCriteriaModel
	 * @throws Exception
	 */
	public function getEntryCriteria($class = 'SectionEntry', $attributes = null)
	{
		$entryType = $this->getEntryType($class);

		if (!$entryType)
		{
			throw new Exception(Blocks::t('No entry type exists with the class handle “{class}”.', array('class' => $class)));
		}

		return new EntryCriteriaModel($attributes, $entryType);
	}

	/**
	 * Finds entries.
	 *
	 * @param mixed $criteria
	 * @return array
	 */
	public function findEntries($criteria = null)
	{
		$entries = array();
		$subquery = $this->buildEntriesQuery($criteria);

		if ($subquery)
		{
			$query = blx()->db->createCommand()
				//->select('r.id, r.type, r.postDate, r.expiryDate, r.enabled, r.archived, r.dateCreated, r.dateUpdated, r.locale, r.title, r.uri, r.sectionId, r.slug')
				->select('*')
				->from('('.$subquery->getText().') AS '.blx()->db->quoteTableName('r'))
				->group('r.id');

			$query->params = $subquery->params;

			if ($criteria->order)
			{
				$query->order($criteria->order);
			}

			if ($criteria->offset)
			{
				$query->offset($criteria->offset);
			}

			if ($criteria->limit)
			{
				$query->limit($criteria->limit);
			}

			$result = $query->queryAll();

			$entryType = $criteria->getEntryType();
			$indexBy = $criteria->indexBy;

			foreach ($result as $row)
			{
				$entry = $entryType->populateEntryModel($row);

				if ($indexBy)
				{
					$entries[$entry->$indexBy] = $entry;
				}
				else
				{
					$entries[] = $entry;
				}
			}
		}

		return $entries;
	}

	/**
	 * Finds an entry.
	 *
	 * @param mixed $criteria
	 * @return SectionEntryModel|null
	 */
	public function findEntry($criteria = null)
	{
		$query = $this->buildEntriesQuery($criteria);

		if ($query)
		{
			$result = $query->queryRow();

			if ($result)
			{
				return $criteria->getEntryType()->populateEntryModel($result);
			}
		}
	}

	/**
	 * Gets the total number of entries.
	 *
	 * @param mixed $criteria
	 * @return int
	 */
	public function getTotalEntries($criteria = null)
	{
		$subquery = $this->buildEntriesQuery($criteria);

		if ($subquery)
		{
			$subquery->select('e.id')->group('e.id');

			$query = blx()->db->createCommand()
				->from('('.$subquery->getText().') AS '.blx()->db->quoteTableName('r'));

			$query->params = $subquery->params;

			return $query->count('r.id');
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns a DbCommand instance ready to search for entries based on a given entry criteria.
	 *
	 * @param mixed &$criteria
	 * @return DbCommand|false
	 */
	public function buildEntriesQuery(&$criteria = null)
	{
		if (!($criteria instanceof EntryCriteriaModel))
		{
			$criteria = $this->getEntryCriteria('SectionEntry', $criteria);
		}

		$entryType = $criteria->getEntryType();

		$query = blx()->db->createCommand()
			->select('e.id, e.type, e.postDate, e.expiryDate, e.enabled, e.archived, e.dateCreated, e.dateUpdated, e_i18n.locale, e_i18n.title, e_i18n.uri')
			->from('entries e');

		$whereConditions = array();

		if ($entryType->isLocalizable())
		{
			$query->join('entries_i18n e_i18n', 'e_i18n.entryId = e.id');

			// Locale conditions
			if (!$criteria->locale)
			{
				$criteria->locale = blx()->language;
			}

			$localeIds = array_unique(array_merge(
				array($criteria->locale),
				blx()->i18n->getSiteLocaleIds()
			));

			$quotedLocaleColumn = blx()->db->quoteColumnName('e_i18n.locale');

			if (count($localeIds) == 1)
			{
				$whereConditions[] = 'e_i18n.locale = :locale';
				$query->params[':locale'] = $localeIds[0];
			}
			else
			{
				$quotedLocales = array();
				$localeOrder = array();

				foreach ($localeIds as $localeId)
				{
					$quotedLocale = blx()->db->quoteValue($localeId);
					$quotedLocales[] = $quotedLocale;
					$localeOrder[] = "({$quotedLocaleColumn} = {$quotedLocale}) DESC";
				}

				$whereConditions[] = "{$quotedLocaleColumn} IN (".implode(', ', $quotedLocales).')';
				$query->order($localeOrder);
			}
		}
		else
		{
			$query->leftJoin('entries_i18n e_i18n', 'e.id = e_i18n.entryId');
		}

		// The rest
		if ($criteria->id)
		{
			$whereConditions[] = DbHelper::parseParam('e.id', $criteria->id, $query->params);
		}

		if ($criteria->uri)
		{
			$whereConditions[] = DbHelper::parseParam('e_i18n.uri', $criteria->uri, $query->params);
		}

		if ($criteria->after)
		{
			$whereConditions[] = DbHelper::parseDateParam('e.postDate', '>=', $criteria->after, $query->params);
		}

		if ($criteria->before)
		{
			$whereConditions[] = DbHelper::parseDateParam('e.postDate', '<', $criteria->before, $query->params);
		}

		if ($criteria->archived)
		{
			$whereConditions[] = 'e.archived = 1';
		}
		else
		{
			$whereConditions[] = 'e.archived = 0';

			if ($criteria->status)
			{
				$statusCondition = $this->_getEntryStatusCondition($criteria->status);

				if ($statusCondition)
				{
					$whereConditions[] = $statusCondition;
				}
			}
		}

		// Apply the conditions
		if (count($whereConditions) == 1)
		{
			$query->where($whereConditions[0]);
		}
		else
		{
			array_unshift($whereConditions, 'and');
			$query->where($whereConditions);
		}

		// Give the entry type a chance to make any changes
		$entryType = $criteria->getEntryType();

		if ($entryType->modifyEntriesQuery($query, $criteria) !== false)
		{
			return $query;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the entry status conditions.
	 *
	 * @access private
	 * @param $statusParam
	 * @return array
	 */
	private function _getEntryStatusCondition($statusParam)
	{
		$statusConditions = array();
		$statuses = ArrayHelper::stringToArray($statusParam);

		foreach ($statuses as $status)
		{
			$status = strtolower($status);
			$currentTimeDb = DateTimeHelper::currentTimeForDb();

			switch ($status)
			{
				case 'live':
				{
					$statusConditions[] = array('and',
						'e.enabled = 1',
						"e.postDate <= '{$currentTimeDb}'",
						array('or', 'e.expiryDate is null', "e.expiryDate > '{$currentTimeDb}'")
					);
					break;
				}
				case 'pending':
				{
					$statusConditions[] = array('and',
						'e.enabled = 1',
						"e.postDate > '{$currentTimeDb}'"
					);
					break;
				}
				case 'expired':
				{
					$statusConditions[] = array('and',
						'e.enabled = 1',
						'e.expiryDate is not null',
						"e.expiryDate <= '{$currentTimeDb}'"
					);
					break;
				}
				case 'disabled':
				{
					$statusConditions[] = 'e.enabled != 1';
				}
			}
		}

		if ($statusConditions)
		{
			if (count($statusConditions) == 1)
			{
				return $statusConditions[0];
			}
			else
			{
				array_unshift($conditions, 'or');
				return $statusConditions;
			}
		}
	}

	/**
	 * Returns the content record for a given entry and locale.
	 *
	 * @param int $entryId
	 * @param string|null $localeId
	 * @return EntryContentRecord|null
	 */
	public function getEntryContentRecord($entryId, $localeId = null)
	{
		$attributes = array('entryId' => $entryId);

		if ($localeId)
		{
			$attributes['locale'] = $localeId;
		}

		return EntryContentRecord::model()->findByAttributes($attributes);
	}

	/**
	 * Returns the content for a given entry and locale.
	 *
	 * @param int $entryId
	 * @param string|null $localeId
	 * @return array|null
	 */
	public function getEntryContent($entryId, $localeId = null)
	{
		$record = $this->getEntryContentRecord($entryId, $localeId);

		if ($record)
		{
			return $record->getAttributes();
		}
	}

	/**
	 * Preps an EntryContentRecord to be saved with an entry's data.
	 *
	 * @param EntryModel $entry
	 * @param FieldLayoutModel $fieldLayout
	 * @param stirng|null $localeId
	 * @return EntryContentRecord
	 */
	public function prepEntryContent(EntryModel $entry, FieldLayoutModel $fieldLayout, $localeId = null)
	{
		if ($entry->id)
		{
			$contentRecord = $this->getEntryContentRecord($entry->id, $localeId);
		}

		if (empty($contentRecord))
		{
			$contentRecord = new EntryContentRecord();
			$contentRecord->entryId = $entry->id;

			if ($localeId)
			{
				$contentRecord->locale = $localeId;
			}
			else
			{
				$contentRecord->locale = blx()->i18n->getPrimarySiteLocale()->getId();
			}
		}

		// Set the required fields from the layout
		$requiredFields = array();

		foreach ($fieldLayout->getFields() as $field)
		{
			if ($field->required)
			{
				$requiredFields[] = $field->fieldId;
			}
		}

		if ($requiredFields)
		{
			$contentRecord->setRequiredFields($requiredFields);
		}

		// Populate the fields' content
		foreach (blx()->fields->getAllFields() as $field)
		{
			$fieldType = blx()->fields->populateFieldType($field);
			$fieldType->entry = $entry;

			if ($fieldType->defineContentAttribute())
			{
				$handle = $field->handle;
				$contentRecord->$handle = $fieldType->getPostData();
			}
		}

		return $contentRecord;
	}

	/**
	 * Performs post-save entry operations, such as calling all fieldtypes' onAfterEntrySave() methods.
	 *
	 * @access private
	 * @param EntryModel $entry
	 * @param EntryContentRecord $entry
	 */
	private function _postSaveOperations(EntryModel $entry, EntryContentRecord $contentRecord)
	{
		if (Blocks::hasPackage(BlocksPackage::Language))
		{
			// Get the other locales' content records
			$otherContentRecords = EntryContentRecord::model()->findAll(
				'entryId = :entryId AND locale != :locale',
				array(':entryId' => $entry->id, ':locale' => $contentRecord->locale)
			);
		}

		$updateOtherContentRecords = (Blocks::hasPackage(BlocksPackage::Language) && $otherContentRecords);

		$fields = blx()->fields->getAllFields();
		$fieldTypes = array();

		foreach ($fields as $field)
		{
			$fieldType = blx()->fields->populateFieldType($field);
			$fieldType->entry = $entry;
			$fieldTypes[] = $fieldType;

			// If this field isn't translatable, we should set its new value on the other content records
			if (!$field->translatable && $updateOtherContentRecords && $fieldType->defineContentAttribute())
			{
				$handle = $field->handle;

				foreach ($otherContentRecords as $otherContentRecord)
				{
					$otherContentRecord->$handle = $contentRecord->$handle;
				}
			}
		}

		// Update each of the other content records
		if ($updateOtherContentRecords)
		{
			foreach ($otherContentRecords as $otherContentRecord)
			{
				$otherContentRecord->save();
			}
		}

		// Now that everything is finally saved, call fieldtypes' onAfterEntrySave();
		foreach ($fieldTypes as $fieldType)
		{
			$fieldType->onAfterEntrySave();
		}
	}

	/**
	 * Saves an entry's content.
	 *
	 * @param EntryModel $entry
	 * @param FieldLayoutModel $fieldLayout
	 * @param stirng|null $localeId
	 */
	public function saveEntryContent(EntryModel $entry, FieldLayoutModel $fieldLayout, $localeId = null)
	{
		if (!$entry->id)
		{
			throw new Exception(Blocks::t('Cannot save the content of an unsaved entry.'));
		}

		$contentRecord = $this->prepEntryContent($entry, $fieldLayout, $localeId);

		if ($contentRecord->save())
		{
			$this->_postSaveOperations($entry, $contentRecord);
			return true;
		}
		else
		{
			$entry->addErrors($contentRecord->getErrors());
			return false;
		}
	}

	/**
	 * Returns an entry's URI for a given locale.
	 *
	 * @param int $entryId
	 * @param string $localeId
	 * @return string
	 */
	public function getEntryUriForLocale($entryId, $localeId)
	{
		return blx()->db->createCommand()
			->select('uri')
			->from('entries_i18n')
			->where(array('entryId' => $entryId, 'locale' => $localeId))
			->queryScalar();
	}

	/**
	 * Returns the CP edit URL for a given entry.
	 *
	 * @param EntryModel $entry
	 * @return string|null
	 */
	public function getCpEditUrlForEntry(EntryModel $entry)
	{
		$entryType = $this->getEntryType($entry->type);

		if ($entryType)
		{
			$uri = $entryType->getCpEditUriForEntry($entry);

			if ($uri !== false)
			{
				return UrlHelper::getCpUrl($uri);
			}
		}
	}

	/**
	 * Returns the localization record for a given entry and locale.
	 *
	 * @param int $entryId
	 * @param string $locale
	 */
	public function getEntryLocalizationRecord($entryId, $localeId)
	{
		return EntryLocalizationRecord::model()->findByAttributes(array(
			'entryId' => $entryId,
			'locale'  => $localeId
		));
	}

	/**
	 * Deletes an entry(s) by its ID(s).
	 *
	 * @param int|array $entryId
	 * @return bool
	 */
	public function deleteEntryById($entryId)
	{
		if (is_array($entryId))
		{
			$condition = array('in', 'id', $entryId);
		}
		else
		{
			$condition = array('id' => $entryId);
		}

		blx()->db->createCommand()->delete('entries', $condition);

		return true;
	}

	/**
	 * Returns tags by a given entry ID.
	 *
	 * @param $entryId
	 * @return array
	 */
	public function getTagsByEntryId($entryId)
	{
		$tags = array();

		$entryRecord = EntryRecord::model()->findByPk($entryId);
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
	 * @param SectionEntryModel $entry
	 * @throws Exception
	 * @return bool
	 */
	public function saveEntry(SectionEntryModel $entry)
	{
		$section = blx()->sections->getSectionById($entry->sectionId);

		if (!$section)
		{
			throw new Exception(Blocks::t('No section exists with the ID “{id}”', array('id' => $entry->sectionId)));
		}

		$sectionLocales = $section->getLocales();

		if (!isset($sectionLocales[$entry->locale]))
		{
			throw new Exception(Blocks::t('The section “{section}” is not enabled for the locale {locale}', array('section' => $section->name, 'locale' => $entry->locale)));
		}

		// Set the basic stuff
		if ($entry->id)
		{
			$entryRecord = EntryRecord::model()->with('entryTagEntries')->findById($entry->id);

			if (!$entryRecord)
			{
				throw new Exception(Blocks::t('No entry exists with the ID “{id}”', array('id' => $entry->id)));
			}
		}
		else
		{
			$entryRecord = new EntryRecord();
			$entryRecord->type = 'SectionEntry';
		}

		$entryRecord->postDate   = DateTimeHelper::normalizeDate($entry->postDate, true);
		$entryRecord->expiryDate = DateTimeHelper::normalizeDate($entry->expiryDate);
		$entryRecord->enabled    = $entry->enabled;

		$entryRecord->validate();
		$entry->addErrors($entryRecord->getErrors());

		// Section entry data
		if ($entry->id)
		{
			$sectionEntryRecord = SectionEntryRecord::model()->findById($entry->id);
		}

		if (empty($sectionEntryRecord))
		{
			$sectionEntryRecord = new SectionEntryRecord();
		}

		$sectionEntryRecord->sectionId = $entry->sectionId;
		$sectionEntryRecord->authorId  = $entry->authorId;

		$sectionEntryRecord->validate();
		$entry->addErrors($sectionEntryRecord->getErrors());

		// Section entry localization data
		if ($entry->id)
		{
			$sectionEntryLocaleRecord = SectionEntryLocalizationRecord::model()->findByAttributes(array(
				'entryId' => $entry->id,
				'locale'  => $entry->locale
			));
		}

		if (empty($sectionEntryLocaleRecord))
		{
			$sectionEntryLocaleRecord = new SectionEntryLocalizationRecord();
			$sectionEntryLocaleRecord->sectionId = $entry->sectionId;
			$sectionEntryLocaleRecord->locale    = $entry->locale;
		}

		if ($sectionEntryLocaleRecord->isNewRecord() || $entry->slug != $sectionEntryLocaleRecord->slug)
		{
			$this->_generateEntrySlug($entry);
			$sectionEntryLocaleRecord->slug = $entry->slug;
		}

		$sectionEntryLocaleRecord->validate();
		$entry->addErrors($sectionEntryLocaleRecord->getErrors());

		// Entry localization data
		if ($entry->id)
		{
			$entryLocaleRecord = EntryLocalizationRecord::model()->findByAttributes(array(
				'entryId' => $entry->id,
				'locale'  => $entry->locale
			));
		}

		if (empty($entryLocaleRecord))
		{
			$entryLocaleRecord = new EntryLocalizationRecord();
			$entryLocaleRecord->locale = $entry->locale;
		}

		$entryLocaleRecord->title = $entry->title;

		if ($section->hasUrls)
		{
			// Make sure the section's URL format is valid. This shouldn't be possible due to section validation,
			// but it's not enforced by the DB, so anything is possible.
			$urlFormat = $sectionLocales[$entry->locale]->urlFormat;

			if (!$urlFormat || strpos($urlFormat, '{slug}') === false)
			{
				throw new Exception(Blocks::t('The section “{section}” doesn’t have a valid URL Format.', array(
					'section' => Blocks::t($section->name)
				)));
			}

			$entryLocaleRecord->uri = str_replace('{slug}', $entry->slug, $urlFormat);
		}

		$entryLocaleRecord->validate();
		$entry->addErrors($entryLocaleRecord->getErrors());

		// Entry content
		$contentRecord = $this->prepEntryContent($entry, $section->getFieldLayout(), $entry->locale);
		$contentRecord->validate();
		$entry->addErrors($contentRecord->getErrors());

		// Tags
		$entryTagRecords = $this->_processTags($entry, $entryRecord);
		$tagErrors = $this->_validateEntryTagRecords($entryTagRecords);
		$entry->addErrors($tagErrors);

		if (!$entry->hasErrors())
		{
			$entryRecord->save(false);

			$entry->postDate   = $entryRecord->postDate;
			$entry->expiryDate = $entryRecord->expiryDate;

			// Now that we have an entry ID, save it on the other stuff
			if (!$entry->id)
			{
				$entry->id = $entryRecord->id;
				$sectionEntryRecord->id = $entry->id;
			}

			$sectionEntryRecord->save(false);

			$sectionEntryLocaleRecord->entryId = $entry->id;
			$entryLocaleRecord->entryId = $entry->id;
			$contentRecord->entryId = $entry->id;

			// Save the other records
			$sectionEntryLocaleRecord->save(false);
			$entryLocaleRecord->save(false);
			$contentRecord->save(false);

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
						$entryTagEntryRecord->entryId = $entryRecord->id;
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
							'entryId' => $entryRecord->id
						));

						$this->_updateTagCount($deleteEntryTagRecord);
					}
				}
			}

			// Save a new version
			if (Blocks::hasPackage(BlocksPackage::PublishPro))
			{
				blx()->entryRevisions->saveVersion($entry);
			}

			// Perform some post-save operations
			$this->_postSaveOperations($entry, $contentRecord);

			return true;
		}
		else
		{
			return false;
		}
	}

	// Entry types
	// ===========

	/**
	 * Returns all installed entry types.
	 *
	 * @return array
	 */
	public function getAllEntryTypes()
	{
		return blx()->components->getComponentsByType(ComponentType::Entry);
	}

	/**
	 * Gets an entry type.
	 *
	 * @param string $class
	 * @return BaseEntryType|null
	 */
	public function getEntryType($class)
	{
		return blx()->components->getComponentByTypeAndClass(ComponentType::Entry, $class);
	}

	// Private methods
	// ===============

	/**
	 * Generates an entry slug based on its title.
	 *
	 * @access private
	 * @param SectionEntryModel $entry
	 */
	private function _generateEntrySlug(SectionEntryModel $entry)
	{
		$slug = ($entry->slug ? $entry->slug : $entry->title);

		// Remove HTML tags
		$slug = preg_replace('/<(.*?)>/', '', $slug);

		// Make it lowercase
		$slug = strtolower($slug);

		// Convert extended ASCII characters to basic ASCII
		$slug = StringHelper::asciiString($slug);

		// Slug must start and end with alphanumeric characters
		$slug = preg_replace('/^[^a-z0-9]+/', '', $slug);
		$slug = preg_replace('/[^a-z0-9]+$/', '', $slug);

		// Get the "words"
		$slug = implode('-', array_filter(preg_split('/[^a-z0-9]+/', $slug)));

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

				$totalEntries = blx()->db->createCommand()
					->select('count(id)')
					->from('sectionentries_i18n')
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
