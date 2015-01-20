<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\DbCommand;
use craft\app\enums\ElementType;
use craft\app\enums\SectionType;
use craft\app\errors\Exception;
use craft\app\events\EntryTypeEvent;
use craft\app\events\SectionEvent;
use craft\app\helpers\DateTimeHelper;
use craft\app\models\EntryType as EntryTypeModel;
use craft\app\models\Section as SectionModel;
use craft\app\models\SectionLocale as SectionLocaleModel;
use craft\app\models\Structure as StructureModel;
use craft\app\records\EntryType as EntryTypeRecord;
use craft\app\records\Section as SectionRecord;
use craft\app\records\SectionLocale as SectionLocaleRecord;
use yii\base\Component;

/**
 * Class Sections service.
 *
 * An instance of the Sections service is globally accessible in Craft via [[Application::sections `Craft::$app->sections`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Sections extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event SectionEvent The event that is triggered before a section is saved.
     *
     * You may set [[SectionEvent::performAction]] to `false` to prevent the section from getting saved.
     */
    const EVENT_BEFORE_SAVE_SECTION = 'beforeSaveSection';

	/**
     * @event SectionEvent The event that is triggered after a section is saved.
     */
    const EVENT_AFTER_SAVE_SECTION = 'afterSaveSection';

	/**
     * @event EntryTypeEvent The event that is triggered before an entry type is saved.
     *
     * You may set [[EntryTypeEvent::performAction]] to `false` to prevent the entry type from getting saved.
     */
    const EVENT_BEFORE_SAVE_ENTRY_TYPE = 'beforeSaveEntryType';

	/**
     * @event EntryTypeEvent The event that is triggered after an entry type is saved.
     */
    const EVENT_AFTER_SAVE_ENTRY_TYPE = 'afterSaveEntryType';

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
			$this->_allSectionIds = [];

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
			$this->_editableSectionIds = [];

			foreach ($this->getAllSectionIds() as $sectionId)
			{
				if (Craft::$app->getUser()->checkPermission('editEntries:'.$sectionId))
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

			$this->_sectionsById = [];

			$typeCounts = [
				SectionType::Single => 0,
				SectionType::Channel => 0,
				SectionType::Structure => 0
			];

			foreach ($results as $result)
			{
				$type = $result['type'];

				if (Craft::$app->getEdition() >= Craft::Client || $typeCounts[$type] < $this->typeLimits[$type])
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
			$sections = [];

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
		$editableSections = [];

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
		$sections = [];

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
				->where('sections.id = :sectionId', [':sectionId' => $sectionId])
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
			->where('sections.handle = :sectionHandle', [':sectionHandle' => $sectionHandle])
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
		$records = Craft::$app->getDb()->createCommand()
			->select('*')
			->from('sections_i18n sections_i18n')
			->join('locales locales', 'locales.locale = sections_i18n.locale')
			->where('sections_i18n.sectionId = :sectionId', [':sectionId' => $sectionId])
			->order('locales.sortOrder')
			->queryAll();

		return SectionLocaleModel::populateModels($records, $indexBy);
	}

	/**
	 * Saves a section.
	 *
	 * @param SectionModel $section
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveSection(SectionModel $section)
	{
		if ($section->id)
		{
			$sectionRecord = SectionRecord::model()->with('structure')->findById($section->id);

			if (!$sectionRecord)
			{
				throw new Exception(Craft::t('app', 'No section exists with the ID “{id}”.', ['id' => $section->id]));
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
			$section->addError('type', Craft::t('app', 'You can’t add any more {type} sections.', ['type' => Craft::t('app', ucfirst($section->type))]));
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
			$section->addError('localeErrors', Craft::t('app', 'At least one locale must be selected for the section.'));
		}

		foreach ($sectionLocales as $localeId => $sectionLocale)
		{
			if ($section->type == SectionType::Single)
			{
				$errorKey = 'urlFormat-'.$localeId;

				if (empty($sectionLocale->urlFormat))
				{
					$section->addError($errorKey, Craft::t('app', 'URI cannot be blank.'));
				}
				else if ($section)
				{
					// Make sure no other elements are using this URI already
					$query = Craft::$app->getDb()->createCommand()
						->from('elements_i18n elements_i18n')
						->where(
							['and', 'elements_i18n.locale = :locale', 'elements_i18n.uri = :uri'],
							[':locale' => $localeId, ':uri' => $sectionLocale->urlFormat]
						);

					if ($section->id)
					{
						$query->join('entries entries', 'entries.id = elements_i18n.elementId')
							->andWhere('entries.sectionId != :sectionId', [':sectionId' => $section->id]);
					}

					$count = $query->count('elements_i18n.id');

					if ($count)
					{
						$section->addError($errorKey, Craft::t('app', 'This URI is already in use.'));
					}
				}

				$sectionLocale->nestedUrlFormat = null;
			}
			else if ($section->hasUrls)
			{
				$urlFormatAttributes = ['urlFormat'];
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
					if (!$sectionLocale->validate([$attribute]))
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
			$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

			try
			{
				// Fire a 'beforeSaveSection' event
				$event = new SectionEvent([
					'section' => $section
				]);

				$this->trigger(static::EVENT_BEFORE_SAVE_SECTION, $event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					// Do we need to create a structure?
					if ($section->type == SectionType::Structure)
					{
						if (!$isNewSection && $oldSection->type == SectionType::Structure)
						{
							$structure = Craft::$app->structures->getStructureById($oldSection->structureId);
							$isNewStructure = false;
						}

						if (empty($structure))
						{
							$structure = new StructureModel();
							$isNewStructure = true;
						}

						$structure->maxLevels = $section->maxLevels;
						Craft::$app->structures->saveStructure($structure);

						$sectionRecord->structureId = $structure->id;
						$section->structureId = $structure->id;
					}
					else
					{
						if (!$isNewSection && $oldSection->structureId)
						{
							// Delete the old one
							Craft::$app->structures->deleteStructureById($oldSection->structureId);
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
					$newLocaleData = [];

					if (!$isNewSection)
					{
						// Get the old section locales
						$oldSectionLocaleRecords = SectionLocaleRecord::model()->findAllByAttributes([
							'sectionId' => $section->id
						]);

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
								Craft::$app->getDb()->createCommand()->update('sections_i18n', [
									'enabledByDefault' => (int)$locale->enabledByDefault,
									'urlFormat'        => $locale->urlFormat,
									'nestedUrlFormat'  => $locale->nestedUrlFormat
								], [
									'id' => $oldLocale->id
								]);
							}
						}
						else
						{
							$newLocaleData[] = [$section->id, $localeId, (int)$locale->enabledByDefault, $locale->urlFormat, $locale->nestedUrlFormat];
						}
					}

					// Insert the new locales
					Craft::$app->getDb()->createCommand()->insertAll('sections_i18n',
						['sectionId', 'locale', 'enabledByDefault', 'urlFormat', 'nestedUrlFormat'],
						$newLocaleData
					);

					if (!$isNewSection)
					{
						// Drop any locales that are no longer being used, as well as the associated entry/element locale
						// rows

						$droppedLocaleIds = array_diff(array_keys($oldSectionLocales), array_keys($sectionLocales));

						if ($droppedLocaleIds)
						{
							Craft::$app->getDb()->createCommand()->delete('sections_i18n',
								['and', 'sectionId = :sectionId', ['in', 'locale', $droppedLocaleIds]],
								[':sectionId' => $section->id]
							);
						}
					}

					// Make sure there's at least one entry type for this section
					$entryTypeId = null;

					if (!$isNewSection)
					{
						// Let's grab all of the entry type IDs to save ourselves a query down the road if this is a Single
						$entryTypeIds = Craft::$app->getDb()->createCommand()
							->select('id')
							->from('entrytypes')
							->where('sectionId = :sectionId', [':sectionId' => $section->id])
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
							$entryType->titleLabel = Craft::t('app', 'Title');
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
								$entryIds = Craft::$app->getDb()->createCommand()
									->select('id')
									->from('entries')
									->where('sectionId = :sectionId', [':sectionId' => $section->id])
									->queryColumn();

								if ($entryIds)
								{
									$singleEntryId = array_shift($entryIds);

									// If there are any more, get rid of them
									if ($entryIds)
									{
										Craft::$app->elements->deleteElementById($entryIds);
									}

									// Make sure it's enabled and all that.

									Craft::$app->getDb()->createCommand()->update('elements', [
										'enabled'  => 1,
										'archived' => 0,
									], [
										'id' => $singleEntryId
									]);

									Craft::$app->getDb()->createCommand()->update('entries', [
										'typeId'     => $entryTypeId,
										'authorId'   => null,
										'postDate'   => DateTimeHelper::currentTimeForDb(),
										'expiryDate' => null,
									], [
										'id' => $singleEntryId
									]);
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

								Craft::$app->getDb()->createCommand()->insert('elements', [
									'type' => ElementType::Entry
								]);

								$singleEntryId = Craft::$app->getDb()->getLastInsertID();

								Craft::$app->getDb()->createCommand()->insert('entries', [
									'id'        => $singleEntryId,
									'sectionId' => $section->id,
									'typeId'    => $entryTypeId,
									'postDate'  => DateTimeHelper::currentTimeForDb()
								]);
							}

							// Now make sure we've got all of the i18n rows in place.
							foreach ($sectionLocales as $localeId => $sectionLocale)
							{
								Craft::$app->getDb()->createCommand()->insertOrUpdate('elements_i18n', [
									'elementId' => $singleEntryId,
									'locale'    => $localeId,
								], [
									'slug' => $section->handle,
									'uri'  => $sectionLocale->urlFormat
								]);

								Craft::$app->getDb()->createCommand()->insertOrUpdate('content', [
									'elementId' => $singleEntryId,
									'locale'    => $localeId
								], [
									'title' => $section->name
								]);
							}

							break;
						}

						case SectionType::Structure:
						{
							if (!$isNewSection && $isNewStructure)
							{
								// Add all of the entries to the structure
								$criteria = Craft::$app->elements->getCriteria(ElementType::Entry);
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
										Craft::$app->structures->appendToRoot($section->structureId, $entry, 'insert');
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
						$criteria = Craft::$app->elements->getCriteria(ElementType::Entry);
						$criteria->locale = array_shift(array_keys($oldSectionLocales));
						$criteria->sectionId = $section->id;
						$criteria->status = null;
						$criteria->localeEnabled = null;
						$criteria->limit = null;

						Craft::$app->tasks->createTask('ResaveElements', Craft::t('app', 'Resaving {section} entries', ['section' => $section->name]), [
							'elementType' => ElementType::Entry,
							'criteria'    => $criteria->getAttributes()
						]);
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
			// Fire an 'afterSaveSection' event
			$this->trigger(static::EVENT_AFTER_SAVE_SECTION, new SectionEvent([
				'section' => $section
			]));
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

		$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Grab the entry ids so we can clean the elements table.
			$entryIds = Craft::$app->getDb()->createCommand()
				->select('id')
				->from('entries')
				->where(['sectionId' => $sectionId])
				->queryColumn();

			Craft::$app->elements->deleteElementById($entryIds);

			// Delete the structure, if there is one
			$structureId = Craft::$app->getDb()->createCommand()
				->select('structureId')
				->from('sections')
				->where(['id' => $sectionId])
				->queryScalar();

			if ($structureId)
			{
				Craft::$app->structures->deleteStructureById($structureId);
			}

			// Delete the section.
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('sections', ['id' => $sectionId]);

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
			$oldTemplatesPath = Craft::$app->path->getTemplatesPath();
			Craft::$app->path->setTemplatesPath(Craft::$app->path->getSiteTemplatesPath());

			// Does the template exist?
			$templateExists = Craft::$app->templates->doesTemplateExist($section->template);

			// Restore the original template path
			Craft::$app->path->setTemplatesPath($oldTemplatesPath);

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
		$records = EntryTypeRecord::model()->ordered()->findAllByAttributes([
			'sectionId' => $sectionId
		]);

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
		$entryTypeRecords = EntryTypeRecord::model()->findAllByAttributes([
			'handle' => $entryTypeHandle
		]);

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
				throw new Exception(Craft::t('app', 'No entry type exists with the ID “{id}”.', ['id' => $entryType->id]));
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
			$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

			try
			{
				// Fire a 'beforeSaveEntryType' event
				$event = new EntryTypeEvent([
					'entryType'      => $entryType,
					'isNewEntryType' => $isNewEntryType
				]);

				$this->trigger(static::EVENT_BEFORE_SAVE_ENTRY_TYPE, $event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					if (!$isNewEntryType && $oldEntryType->fieldLayoutId)
					{
						// Drop the old field layout
						Craft::$app->fields->deleteLayoutById($oldEntryType->fieldLayoutId);
					}

					// Save the new one
					$fieldLayout = $entryType->getFieldLayout();
					Craft::$app->fields->saveLayout($fieldLayout);

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
			// Fire an 'afterSaveEntryType' event
			$this->trigger(static::EVENT_AFTER_SAVE_ENTRY_TYPE, new EntryTypeEvent([
				'entryType' => $entryType
			]));
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
		$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

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

		$transaction = Craft::$app->getDb()->getCurrentTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Delete the field layout
			 $query = Craft::$app->getDb()->createCommand()
				->select('fieldLayoutId')
				->from('entrytypes');

			if (is_array($entryTypeId))
			{
				$query->where(['in', 'id', $entryTypeId]);
			}
			else
			{
				$query->where(['id' => $entryTypeId]);
			}

			$fieldLayoutIds = $query->queryColumn();

			if ($fieldLayoutIds)
			{
				Craft::$app->fields->deleteLayoutById($fieldLayoutIds);
			}

			// Grab the entry IDs so we can clean the elements table.
			$query = Craft::$app->getDb()->createCommand()
				->select('id')
				->from('entries');

			if (is_array($entryTypeId))
			{
				$query->where(['in', 'typeId', $entryTypeId]);
			}
			else
			{
				$query->where(['typeId' => $entryTypeId]);
			}

			$entryIds = $query->queryColumn();

			Craft::$app->elements->deleteElementById($entryIds);

			// Delete the entry type.
			if (is_array($entryTypeId))
			{
				$affectedRows = Craft::$app->getDb()->createCommand()->delete('entrytypes', ['in', 'id', $entryTypeId]);
			}
			else
			{
				$affectedRows = Craft::$app->getDb()->createCommand()->delete('entrytypes', ['id' => $entryTypeId]);
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
		$conditions = ['and', 'sections.type = :type', 'sections_i18n.urlFormat = :homeUri'];
		$params     = [':type' => SectionType::Single, ':homeUri' => '__home__'];

		$count = Craft::$app->getDb()->createCommand()
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
		if (Craft::$app->getEdition() >= Craft::Client)
		{
			return true;
		}
		else
		{
			if (isset($this->typeLimits[$type]))
			{
				$count = Craft::$app->getDb()->createCommand()
					->from('sections')
					->where('type = :type', [':type' => $type])
					->count('id');

				return $count < $this->typeLimits[$type];
			}
			else
			{
				return false;
			}
		}
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
		return Craft::$app->getDb()->createCommand()
			->select('sections.id, sections.structureId, sections.name, sections.handle, sections.type, sections.hasUrls, sections.template, sections.enableVersioning, structures.maxLevels')
			->leftJoin('structures structures', 'structures.id = sections.structureId')
			->from('sections sections')
			->order('name');
	}
}
