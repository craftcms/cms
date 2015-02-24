<?php
namespace Craft;

/**
 * Class SectionsService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class SectionsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	public $typeLimits;

	/**
	 * @var
	 */
	private $_allSectionIds;

	/**
	 * @var
	 */
	private $_editableSectionIds;

	/**
	 * @var
	 */
	private $_sectionsById;

	/**
	 * @var bool
	 */
	private $_fetchedAllSections = false;

	/**
	 * @var
	 */
	private $_entryTypesById;

	// Public Methods
	// =========================================================================

	// Sections
	// -------------------------------------------------------------------------

	/**
	 * Returns all of the section IDs.
	 *
	 * @return array All the sections’ IDs.
	 */
	public function getAllSectionIds()
	{
		if (!isset($this->_allSectionIds))
		{
			$this->_allSectionIds = array();

			foreach ($this->getAllSections() as $section)
			{
				$this->_allSectionIds[] = $section->id;
			}
		}

		return $this->_allSectionIds;
	}

	/**
	 * Returns all of the section IDs that are editable by the current user.
	 *
	 * @return array All the editable sections’ IDs.
	 */
	public function getEditableSectionIds()
	{
		if (!isset($this->_editableSectionIds))
		{
			$this->_editableSectionIds = array();

			foreach ($this->getAllSectionIds() as $sectionId)
			{
				if (craft()->userSession->checkPermission('editEntries:'.$sectionId))
				{
					$this->_editableSectionIds[] = $sectionId;
				}
			}
		}

		return $this->_editableSectionIds;
	}

	/**
	 * Returns all sections.
	 *
	 * @param string|null $indexBy
	 *
	 * @return SectionModel[] All the sections.
	 */
	public function getAllSections($indexBy = null)
	{
		if (!$this->_fetchedAllSections)
		{
			$results = $this->_createSectionQuery()
				->queryAll();

			$this->_sectionsById = array();

			$typeCounts = array(
				SectionType::Single => 0,
				SectionType::Channel => 0,
				SectionType::Structure => 0
			);

			foreach ($results as $result)
			{
				$type = $result['type'];

				if (craft()->getEdition() >= Craft::Client || $typeCounts[$type] < $this->typeLimits[$type])
				{
					$section = new SectionModel($result);
					$this->_sectionsById[$section->id] = $section;
					$typeCounts[$type]++;
				}
			}

			$this->_fetchedAllSections = true;
		}

		if ($indexBy == 'id')
		{
			$sections = $this->_sectionsById;
		}
		else if (!$indexBy)
		{
			$sections = array_values($this->_sectionsById);
		}
		else
		{
			$sections = array();
			foreach ($this->_sectionsById as $section)
			{
				$sections[$section->$indexBy] = $section;
			}
		}

		return $sections;
	}

	/**
	 * Returns all editable sections.
	 *
	 * @param string|null $indexBy
	 *
	 * @return SectionModel[] All the editable sections.
	 */
	public function getEditableSections($indexBy = null)
	{
		$editableSectionIds = $this->getEditableSectionIds();
		$editableSections = array();

		foreach ($this->getAllSections() as $section)
		{
			if (in_array($section->id, $editableSectionIds))
			{
				if ($indexBy)
				{
					$editableSections[$section->$indexBy] = $section;
				}
				else
				{
					$editableSections[] = $section;
				}
			}
		}

		return $editableSections;
	}


	/**
	 * Returns all sections of a given type.
	 *
	 * @param string $type
	 *
	 * @return SectionModel[] All the sections of the given type.
	 */
	public function getSectionsByType($type)
	{
		$sections = array();

		foreach ($this->getAllSections() as $section)
		{
			if ($section->type == $type)
			{
				$sections[] = $section;
			}
		}

		return $sections;
	}

	/**
	 * Gets the total number of sections.
	 *
	 * @return int
	 */
	public function getTotalSections()
	{
		return count($this->getAllSectionIds());
	}

	/**
	 * Gets the total number of sections that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSections()
	{
		return count($this->getEditableSectionIds());
	}

	/**
	 * Returns a section by its ID.
	 *
	 * @param int $sectionId
	 *
	 * @return SectionModel|null
	 */
	public function getSectionById($sectionId)
	{
		// If we've already fetched all sections we can save ourselves a trip to the DB for section IDs that don't exist
		if (!$this->_fetchedAllSections &&
			(!isset($this->_sectionsById) || !array_key_exists($sectionId, $this->_sectionsById))
		)
		{
			$result = $this->_createSectionQuery()
				->where('sections.id = :sectionId', array(':sectionId' => $sectionId))
				->queryRow();

			if ($result)
			{
				$section = new SectionModel($result);
			}
			else
			{
				$section = null;
			}

			$this->_sectionsById[$sectionId] = $section;
		}

		if (isset($this->_sectionsById[$sectionId]))
		{
			return $this->_sectionsById[$sectionId];
		}
	}

	/**
	 * Gets a section by its handle.
	 *
	 * @param string $sectionHandle
	 *
	 * @return SectionModel|null
	 */
	public function getSectionByHandle($sectionHandle)
	{
		$result = $this->_createSectionQuery()
			->where('sections.handle = :sectionHandle', array(':sectionHandle' => $sectionHandle))
			->queryRow();

		if ($result)
		{
			$section = new SectionModel($result);
			$this->_sectionsById[$section->id] = $section;

			return $section;
		}
	}

	/**
	 * Returns a section’s locales.
	 *
	 * @param int         $sectionId
	 * @param string|null $indexBy
	 *
	 * @return SectionLocaleModel[] The section’s locales.
	 */
	public function getSectionLocales($sectionId, $indexBy = null)
	{
		$records = craft()->db->createCommand()
			->select('*')
			->from('sections_i18n sections_i18n')
			->join('locales locales', 'locales.locale = sections_i18n.locale')
			->where('sections_i18n.sectionId = :sectionId', array(':sectionId' => $sectionId))
			->order('locales.sortOrder')
			->queryAll();

		return SectionLocaleModel::populateModels($records, $indexBy);
	}

	/**
	 * Saves a section.
	 *
	 * @param SectionModel $section
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSection(SectionModel $section)
	{
		if ($section->id)
		{
			$sectionRecord = SectionRecord::model()->with('structure')->findById($section->id);

			if (!$sectionRecord)
			{
				throw new Exception(Craft::t('No section exists with the ID “{id}”.', array('id' => $section->id)));
			}

			$oldSection = SectionModel::populateModel($sectionRecord);
			$isNewSection = false;
		}
		else
		{
			$sectionRecord = new SectionRecord();
			$isNewSection = true;
		}

		// Shared attributes
		$sectionRecord->name             = $section->name;
		$sectionRecord->handle           = $section->handle;
		$sectionRecord->type             = $section->type;
		$sectionRecord->enableVersioning = $section->enableVersioning;

		if (($isNewSection || $section->type != $oldSection->type) && !$this->canHaveMore($section->type))
		{
			$section->addError('type', Craft::t('You can’t add any more {type} sections.', array('type' => Craft::t(ucfirst($section->type)))));
		}

		// Type-specific attributes
		if ($section->type == SectionType::Single)
		{
			$sectionRecord->hasUrls = $section->hasUrls = true;
		}
		else
		{
			$sectionRecord->hasUrls = $section->hasUrls;
		}

		if ($section->hasUrls)
		{
			$sectionRecord->template = $section->template;
		}
		else
		{
			$sectionRecord->template = $section->template = null;
		}

		$sectionRecord->validate();
		$section->addErrors($sectionRecord->getErrors());

		// Make sure that all of the URL formats are set properly
		$sectionLocales = $section->getLocales();

		if (!$sectionLocales)
		{
			$section->addError('localeErrors', Craft::t('At least one locale must be selected for the section.'));
		}

		foreach ($sectionLocales as $localeId => $sectionLocale)
		{
			if ($section->type == SectionType::Single)
			{
				$errorKey = 'urlFormat-'.$localeId;

				if (empty($sectionLocale->urlFormat))
				{
					$section->addError($errorKey, Craft::t('URI cannot be blank.'));
				}
				else if ($section)
				{
					// Make sure no other elements are using this URI already
					$query = craft()->db->createCommand()
						->from('elements_i18n elements_i18n')
						->where(
							array('and', 'elements_i18n.locale = :locale', 'elements_i18n.uri = :uri'),
							array(':locale' => $localeId, ':uri' => $sectionLocale->urlFormat)
						);

					if ($section->id)
					{
						$query->join('entries entries', 'entries.id = elements_i18n.elementId')
							->andWhere('entries.sectionId != :sectionId', array(':sectionId' => $section->id));
					}

					$count = $query->count('elements_i18n.id');

					if ($count)
					{
						$section->addError($errorKey, Craft::t('This URI is already in use.'));
					}
				}

				$sectionLocale->nestedUrlFormat = null;
			}
			else if ($section->hasUrls)
			{
				$urlFormatAttributes = array('urlFormat');
				$sectionLocale->urlFormatIsRequired = true;

				if ($section->type == SectionType::Structure && $section->maxLevels != 1)
				{
					$urlFormatAttributes[] = 'nestedUrlFormat';
					$sectionLocale->nestedUrlFormatIsRequired = true;
				}
				else
				{
					$sectionLocale->nestedUrlFormat = null;
				}

				foreach ($urlFormatAttributes as $attribute)
				{
					if (!$sectionLocale->validate(array($attribute)))
					{
						$section->addError($attribute.'-'.$localeId, $sectionLocale->getError($attribute));
					}
				}
			}
			else
			{
				$sectionLocale->urlFormat = null;
				$sectionLocale->nestedUrlFormat = null;
			}
		}

		if (!$section->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			try
			{
				// Fire an 'onBeforeSaveSection' event
				$event = new Event($this, array(
					'section'      => $section,
					'isNewSection' => $isNewSection,
				));

				$this->onBeforeSaveSection($event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					// Do we need to create a structure?
					if ($section->type == SectionType::Structure)
					{
						if (!$isNewSection && $oldSection->type == SectionType::Structure)
						{
							$structure = craft()->structures->getStructureById($oldSection->structureId);
							$isNewStructure = false;
						}

						if (empty($structure))
						{
							$structure = new StructureModel();
							$isNewStructure = true;
						}

						$structure->maxLevels = $section->maxLevels;
						craft()->structures->saveStructure($structure);

						$sectionRecord->structureId = $structure->id;
						$section->structureId = $structure->id;
					}
					else
					{
						if (!$isNewSection && $oldSection->structureId)
						{
							// Delete the old one
							craft()->structures->deleteStructureById($oldSection->structureId);
							$sectionRecord->structureId = null;
						}
					}

					$sectionRecord->save(false);

					// Now that we have a section ID, save it on the model
					if ($isNewSection)
					{
						$section->id = $sectionRecord->id;
					}

					// Might as well update our cache of the section while we have it. (It's possible that the URL format
					//includes {section.handle} or something...)
					$this->_sectionsById[$section->id] = $section;

					// Update the sections_i18n table
					$newLocaleData = array();

					if (!$isNewSection)
					{
						// Get the old section locales
						$oldSectionLocaleRecords = SectionLocaleRecord::model()->findAllByAttributes(array(
							'sectionId' => $section->id
						));

						$oldSectionLocales = SectionLocaleModel::populateModels($oldSectionLocaleRecords, 'locale');
					}

					foreach ($sectionLocales as $localeId => $locale)
					{
						// Was this already selected?
						if (!$isNewSection && isset($oldSectionLocales[$localeId]))
						{
							$oldLocale = $oldSectionLocales[$localeId];

							// Has anything changed?
							if ($locale->enabledByDefault != $oldLocale->enabledByDefault || $locale->urlFormat != $oldLocale->urlFormat || $locale->nestedUrlFormat != $oldLocale->nestedUrlFormat)
							{
								craft()->db->createCommand()->update('sections_i18n', array(
									'enabledByDefault' => (int)$locale->enabledByDefault,
									'urlFormat'        => $locale->urlFormat,
									'nestedUrlFormat'  => $locale->nestedUrlFormat
								), array(
									'id' => $oldLocale->id
								));
							}
						}
						else
						{
							$newLocaleData[] = array($section->id, $localeId, (int)$locale->enabledByDefault, $locale->urlFormat, $locale->nestedUrlFormat);
						}
					}

					// Insert the new locales
					craft()->db->createCommand()->insertAll('sections_i18n',
						array('sectionId', 'locale', 'enabledByDefault', 'urlFormat', 'nestedUrlFormat'),
						$newLocaleData
					);

					if (!$isNewSection)
					{
						// Drop any locales that are no longer being used, as well as the associated entry/element locale
						// rows

						$droppedLocaleIds = array_diff(array_keys($oldSectionLocales), array_keys($sectionLocales));

						if ($droppedLocaleIds)
						{
							craft()->db->createCommand()->delete('sections_i18n',
								array('and', 'sectionId = :sectionId', array('in', 'locale', $droppedLocaleIds)),
								array(':sectionId' => $section->id)
							);
						}
					}

					// Make sure there's at least one entry type for this section
					$entryTypeId = null;

					if (!$isNewSection)
					{
						// Let's grab all of the entry type IDs to save ourselves a query down the road if this is a Single
						$entryTypeIds = craft()->db->createCommand()
							->select('id')
							->from('entrytypes')
							->where('sectionId = :sectionId', array(':sectionId' => $section->id))
							->queryColumn();

						if ($entryTypeIds)
						{
							$entryTypeId = array_shift($entryTypeIds);
						}
					}

					if (!$entryTypeId)
					{
						$entryType = new EntryTypeModel();

						$entryType->sectionId = $section->id;
						$entryType->name = $section->name;
						$entryType->handle = $section->handle;

						if ($section->type == SectionType::Single)
						{
							$entryType->hasTitleField = false;
							$entryType->titleLabel = null;
							$entryType->titleFormat = '{section.name|raw}';
						}
						else
						{
							$entryType->hasTitleField = true;
							$entryType->titleLabel = Craft::t('Title');
							$entryType->titleFormat = null;
						}

						$this->saveEntryType($entryType);

						$entryTypeId = $entryType->id;
					}

					// Now, regardless of whether the section type changed or not, let the section type make sure
					// everything is cool

					switch ($section->type)
					{
						case SectionType::Single:
						{
							// In a nut, we want to make sure that there is one and only one Entry Type and Entry for this
							// section. We also want to make sure the entry has rows in the i18n tables
							// for each of the sections' locales.

							$singleEntryId = null;

							if (!$isNewSection)
							{
								// Make sure there's only one entry in this section
								$entryIds = craft()->db->createCommand()
									->select('id')
									->from('entries')
									->where('sectionId = :sectionId', array(':sectionId' => $section->id))
									->queryColumn();

								if ($entryIds)
								{
									$singleEntryId = array_shift($entryIds);

									// If there are any more, get rid of them
									if ($entryIds)
									{
										craft()->elements->deleteElementById($entryIds);
									}

									// Make sure it's enabled and all that.

									craft()->db->createCommand()->update('elements', array(
										'enabled'  => 1,
										'archived' => 0,
									), array(
										'id' => $singleEntryId
									));

									craft()->db->createCommand()->update('entries', array(
										'typeId'     => $entryTypeId,
										'authorId'   => null,
										'postDate'   => DateTimeHelper::currentTimeForDb(),
										'expiryDate' => null,
									), array(
										'id' => $singleEntryId
									));
								}

								// Make sure there's only one entry type for this section
								if ($entryTypeIds)
								{
									$this->deleteEntryTypeById($entryTypeIds);
								}
							}

							if (!$singleEntryId)
							{
								// Create it, baby

								craft()->db->createCommand()->insert('elements', array(
									'type' => ElementType::Entry
								));

								$singleEntryId = craft()->db->getLastInsertID();

								craft()->db->createCommand()->insert('entries', array(
									'id'        => $singleEntryId,
									'sectionId' => $section->id,
									'typeId'    => $entryTypeId,
									'postDate'  => DateTimeHelper::currentTimeForDb()
								));
							}

							// Now make sure we've got all of the i18n rows in place.
							foreach ($sectionLocales as $localeId => $sectionLocale)
							{
								craft()->db->createCommand()->insertOrUpdate('elements_i18n', array(
									'elementId' => $singleEntryId,
									'locale'    => $localeId,
								), array(
									'slug' => $section->handle,
									'uri'  => $sectionLocale->urlFormat
								));

								craft()->db->createCommand()->insertOrUpdate('content', array(
									'elementId' => $singleEntryId,
									'locale'    => $localeId
								), array(
									'title' => $section->name
								));
							}

							break;
						}

						case SectionType::Structure:
						{
							if (!$isNewSection && $isNewStructure)
							{
								// Add all of the entries to the structure
								$criteria = craft()->elements->getCriteria(ElementType::Entry);
								$criteria->locale = array_shift(array_keys($oldSectionLocales));
								$criteria->sectionId = $section->id;
								$criteria->status = null;
								$criteria->localeEnabled = null;
								$criteria->order = 'elements.id';
								$criteria->limit = 25;

								do
								{
									$batchEntries = $criteria->find();

									foreach ($batchEntries as $entry)
									{
										craft()->structures->appendToRoot($section->structureId, $entry, 'insert');
									}

									$criteria->offset += 25;

								} while ($batchEntries);
							}

							break;
						}
					}

					// Finally, deal with the existing entries...

					if (!$isNewSection)
					{
						$criteria = craft()->elements->getCriteria(ElementType::Entry);

						// Get the most-primary locale that this section was already enabled in
						$locales = array_values(array_intersect(craft()->i18n->getSiteLocaleIds(), array_keys($oldSectionLocales)));

						if ($locales)
						{
							$criteria->locale = $locales[0];
							$criteria->sectionId = $section->id;
							$criteria->status = null;
							$criteria->localeEnabled = null;
							$criteria->limit = null;

							craft()->tasks->createTask('ResaveElements', Craft::t('Resaving {section} entries', array('section' => $section->name)), array(
								'elementType' => ElementType::Entry,
								'criteria'    => $criteria->getAttributes()
							));
						}
					}

					$success = true;
				}
				else
				{
					$success = false;
				}

				// Commit the transaction regardless of whether we saved the section, in case something changed
				// in onBeforeSaveSection
				if ($transaction !== null)
				{
					$transaction->commit();
				}
			} catch (\Exception $e)
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
			$success = false;
		}

		if ($success)
		{
			// Fire an 'onSaveSection' event
			$this->onSaveSection(new Event($this, array(
				'section'      => $section,
				'isNewSection' => $isNewSection,
			)));
		}

		return $success;
	}

	/**
	 * Deletes a section by its ID.
	 *
	 * @param int $sectionId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteSectionById($sectionId)
	{
		if (!$sectionId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Grab the entry ids so we can clean the elements table.
			$entryIds = craft()->db->createCommand()
				->select('id')
				->from('entries')
				->where(array('sectionId' => $sectionId))
				->queryColumn();

			craft()->elements->deleteElementById($entryIds);

			// Delete the structure, if there is one
			$structureId = craft()->db->createCommand()
				->select('structureId')
				->from('sections')
				->where(array('id' => $sectionId))
				->queryScalar();

			if ($structureId)
			{
				craft()->structures->deleteStructureById($structureId);
			}

			// Delete the section.
			$affectedRows = craft()->db->createCommand()->delete('sections', array('id' => $sectionId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
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
	 * Returns whether a section’s entries have URLs, and if the section’s template path is valid.
	 *
	 * @param SectionModel $section
	 *
	 * @return bool
	 */
	public function isSectionTemplateValid(SectionModel $section)
	{
		if ($section->hasUrls)
		{
			// Set Craft to the site template path
			$oldTemplatesPath = craft()->path->getTemplatesPath();
			craft()->path->setTemplatesPath(craft()->path->getSiteTemplatesPath());

			// Does the template exist?
			$templateExists = craft()->templates->doesTemplateExist($section->template);

			// Restore the original template path
			craft()->path->setTemplatesPath($oldTemplatesPath);

			if ($templateExists)
			{
				return true;
			}
		}

		return false;
	}

	// Entry Types
	// -------------------------------------------------------------------------

	/**
	 * Returns a section’s entry types.
	 *
	 * @param int         $sectionId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getEntryTypesBySectionId($sectionId, $indexBy = null)
	{
		$records = EntryTypeRecord::model()->ordered()->findAllByAttributes(array(
			'sectionId' => $sectionId
		));

		return EntryTypeModel::populateModels($records, $indexBy);
	}

	/**
	 * Returns an entry type by its ID.
	 *
	 * @param int $entryTypeId
	 *
	 * @return EntryTypeModel|null
	 */
	public function getEntryTypeById($entryTypeId)
	{
		if (!isset($this->_entryTypesById) || !array_key_exists($entryTypeId, $this->_entryTypesById))
		{
			$entryTypeRecord = EntryTypeRecord::model()->findById($entryTypeId);

			if ($entryTypeRecord)
			{
				$this->_entryTypesById[$entryTypeId] = EntryTypeModel::populateModel($entryTypeRecord);
			}
			else
			{
				$this->_entryTypesById[$entryTypeId] = null;
			}
		}

		return $this->_entryTypesById[$entryTypeId];
	}

	/**
	 * Returns entry types that have a given handle.
	 *
	 * @param int $entryTypeHandle
	 *
	 * @return array
	 */
	public function getEntryTypesByHandle($entryTypeHandle)
	{
		$entryTypeRecords = EntryTypeRecord::model()->findAllByAttributes(array(
			'handle' => $entryTypeHandle
		));

		return EntryTypeModel::populateModels($entryTypeRecords);
	}

	/**
	 * Saves an entry type.
	 *
	 * @param EntryTypeModel $entryType
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool
	 */
	public function saveEntryType(EntryTypeModel $entryType)
	{
		if ($entryType->id)
		{
			$entryTypeRecord = EntryTypeRecord::model()->findById($entryType->id);

			if (!$entryTypeRecord)
			{
				throw new Exception(Craft::t('No entry type exists with the ID “{id}”.', array('id' => $entryType->id)));
			}

			$isNewEntryType = false;
			$oldEntryType = EntryTypeModel::populateModel($entryTypeRecord);
		}
		else
		{
			$entryTypeRecord = new EntryTypeRecord();
			$isNewEntryType = true;
		}

		$entryTypeRecord->sectionId     = $entryType->sectionId;
		$entryTypeRecord->name          = $entryType->name;
		$entryTypeRecord->handle        = $entryType->handle;
		$entryTypeRecord->hasTitleField = $entryType->hasTitleField;
		$entryTypeRecord->titleLabel    = ($entryType->hasTitleField ? $entryType->titleLabel : null);
		$entryTypeRecord->titleFormat   = (!$entryType->hasTitleField ? $entryType->titleFormat : null);

		$entryTypeRecord->validate();
		$entryType->addErrors($entryTypeRecord->getErrors());

		if (!$entryType->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			try
			{
				// Fire an 'onBeforeSaveEntryType' event
				$event = new Event($this, array(
					'entryType'      => $entryType,
					'isNewEntryType' => $isNewEntryType
				));

				$this->onBeforeSaveEntryType($event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					if (!$isNewEntryType && $oldEntryType->fieldLayoutId)
					{
						// Drop the old field layout
						craft()->fields->deleteLayoutById($oldEntryType->fieldLayoutId);
					}

					// Save the new one
					$fieldLayout = $entryType->getFieldLayout();
					craft()->fields->saveLayout($fieldLayout);

					// Update the entry type record/model with the new layout ID
					$entryType->fieldLayoutId = $fieldLayout->id;
					$entryTypeRecord->fieldLayoutId = $fieldLayout->id;

					$entryTypeRecord->save(false);

					// Now that we have an entry type ID, save it on the model
					if (!$entryType->id)
					{
						$entryType->id = $entryTypeRecord->id;
					}

					// Might as well update our cache of the entry type while we have it.
					$this->_entryTypesById[$entryType->id] = $entryType;

					$success = true;
				}
				else
				{
					$success = false;
				}

				// Commit the transaction regardless of whether we saved the user, in case something changed
				// in onBeforeSaveEntryType
				if ($transaction !== null)
				{
					$transaction->commit();
				}
			} catch (\Exception $e)
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
			$success = false;
		}

		if ($success)
		{
			// Fire an 'onSaveEntryType' event
			$this->onSaveEntryType(new Event($this, array(
				'entryType'      => $entryType,
				'isNewEntryType' => $isNewEntryType
			)));
		}

		return $success;
	}

	/**
	 * Reorders entry types.
	 *
	 * @param array $entryTypeIds
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function reorderEntryTypes($entryTypeIds)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			foreach ($entryTypeIds as $entryTypeOrder => $entryTypeId)
			{
				$entryTypeRecord = EntryTypeRecord::model()->findById($entryTypeId);
				$entryTypeRecord->sortOrder = $entryTypeOrder+1;
				$entryTypeRecord->save();
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

		return true;
	}

	/**
	 * Deletes an entry type(s) by its ID.
	 *
	 * @param int|array $entryTypeId
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteEntryTypeById($entryTypeId)
	{
		if (!$entryTypeId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the field layout
			 $query = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('entrytypes');

			if (is_array($entryTypeId))
			{
				$query->where(array('in', 'id', $entryTypeId));
			}
			else
			{
				$query->where(array('id' => $entryTypeId));
			}

			$fieldLayoutIds = $query->queryColumn();

			if ($fieldLayoutIds)
			{
				craft()->fields->deleteLayoutById($fieldLayoutIds);
			}

			// Grab the entry IDs so we can clean the elements table.
			$query = craft()->db->createCommand()
				->select('id')
				->from('entries');

			if (is_array($entryTypeId))
			{
				$query->where(array('in', 'typeId', $entryTypeId));
			}
			else
			{
				$query->where(array('typeId' => $entryTypeId));
			}

			$entryIds = $query->queryColumn();

			craft()->elements->deleteElementById($entryIds);

			// Delete the entry type.
			if (is_array($entryTypeId))
			{
				$affectedRows = craft()->db->createCommand()->delete('entrytypes', array('in', 'id', $entryTypeId));
			}
			else
			{
				$affectedRows = craft()->db->createCommand()->delete('entrytypes', array('id' => $entryTypeId));
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
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

	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns whether a homepage section exists.
	 *
	 * @return bool
	 */
	public function doesHomepageExist()
	{
		$conditions = array('and', 'sections.type = :type', 'sections_i18n.urlFormat = :homeUri');
		$params = array(':type' => SectionType::Single, ':homeUri' => '__home__');

		$count = craft()->db->createCommand()
			->from('sections sections')
			->join('sections_i18n sections_i18n', 'sections_i18n.sectionId = sections.id')
			->where($conditions, $params)
			->count('sections.id');

		return (bool) $count;
	}

	/**
	 * Returns whether another section can be added of a given type.
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function canHaveMore($type)
	{
		if (craft()->getEdition() >= Craft::Client)
		{
			return true;
		}
		else
		{
			if (isset($this->typeLimits[$type]))
			{
				$count = craft()->db->createCommand()
					->from('sections')
					->where('type = :type', array(':type' => $type))
					->count('id');

				return $count < $this->typeLimits[$type];
			}
			else
			{
				return false;
			}
		}
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSaveEntryType' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveEntryType(Event $event)
	{
		$this->raiseEvent('onBeforeSaveEntryType', $event);
	}

	/**
	 * Fires an 'onSaveEntryType' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveEntryType(Event $event)
	{
		$this->raiseEvent('onSaveEntryType', $event);
	}

	/**
	 * Fires an 'onBeforeSaveSection' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveSection(Event $event)
	{
		$this->raiseEvent('onBeforeSaveSection', $event);
	}

	/**
	 * Fires an 'onSaveSection' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveSection(Event $event)
	{
		$this->raiseEvent('onSaveSection', $event);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a DbCommand object prepped for retrieving sections.
	 *
	 * @return DbCommand
	 */
	private function _createSectionQuery()
	{
		return craft()->db->createCommand()
			->select('sections.id, sections.structureId, sections.name, sections.handle, sections.type, sections.hasUrls, sections.template, sections.enableVersioning, structures.maxLevels')
			->leftJoin('structures structures', 'structures.id = sections.structureId')
			->from('sections sections')
			->order('name');
	}
}
