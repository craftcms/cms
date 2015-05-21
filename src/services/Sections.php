<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\elements\Entry;
use craft\app\errors\Exception;
use craft\app\events\EntryTypeEvent;
use craft\app\events\SectionEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\models\EntryType;
use craft\app\models\Section;
use craft\app\models\SectionLocale;
use craft\app\models\Structure;
use craft\app\records\EntryType as EntryTypeRecord;
use craft\app\records\Section as SectionRecord;
use craft\app\records\SectionLocale as SectionLocaleRecord;
use craft\app\tasks\ResaveElements;
use yii\base\Component;

/**
 * Class Sections service.
 *
 * An instance of the Sections service is globally accessible in Craft via [[Application::sections `Craft::$app->getSections()`]].
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
	 * @return Section[] All the sections.
	 */
	public function getAllSections($indexBy = null)
	{
		if (!$this->_fetchedAllSections)
		{
			$results = $this->_createSectionQuery()
				->all();

			$this->_sectionsById = [];

			$typeCounts = [
				Section::TYPE_SINGLE => 0,
				Section::TYPE_CHANNEL => 0,
				Section::TYPE_STRUCTURE => 0
			];

			foreach ($results as $result)
			{
				$type = $result['type'];

				if (Craft::$app->getEdition() >= Craft::Client || $typeCounts[$type] < $this->typeLimits[$type])
				{
					$section = new Section($result);
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
	 * @return Section[] All the editable sections.
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
	 * @return Section[] All the sections of the given type.
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
	 * @return Section|null
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
				->one();

			if ($result)
			{
				$section = new Section($result);
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
	 * @return Section|null
	 */
	public function getSectionByHandle($sectionHandle)
	{
		$result = $this->_createSectionQuery()
			->where('sections.handle = :sectionHandle', [':sectionHandle' => $sectionHandle])
			->one();

		if ($result)
		{
			$section = new Section($result);
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
	 * @return SectionLocale[] The section’s locales.
	 */
	public function getSectionLocales($sectionId, $indexBy = null)
	{
		$sectionLocales = (new Query())
			->select('*')
			->from('{{%sections_i18n}} sections_i18n')
			->innerJoin('{{%locales}} locales', 'locales.locale = sections_i18n.locale')
			->where('sections_i18n.sectionId = :sectionId', [':sectionId' => $sectionId])
			->orderBy('locales.sortOrder')
			->indexBy($indexBy)
			->all();

		foreach ($sectionLocales as $key => $value)
		{
			$sectionLocales[$key] = SectionLocale::create($value);
		}

		return $sectionLocales;
	}

	/**
	 * Saves a section.
	 *
	 * @param Section $section
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveSection(Section $section)
	{
		if ($section->id)
		{
			$sectionRecord = SectionRecord::find()
				->where(['id' => $section->id])
				->with('structure')
				->one();

			if (!$sectionRecord)
			{
				throw new Exception(Craft::t('app', 'No section exists with the ID “{id}”.', ['id' => $section->id]));
			}

			$oldSection = Section::create($sectionRecord);
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
		if ($section->type == Section::TYPE_SINGLE)
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
			if ($section->type == Section::TYPE_SINGLE)
			{
				$errorKey = 'urlFormat-'.$localeId;

				if (empty($sectionLocale->urlFormat))
				{
					$section->addError($errorKey, Craft::t('app', 'URI cannot be blank.'));
				}
				else if ($section)
				{
					// Make sure no other elements are using this URI already
					$query = (new Query())
						->from('{{%elements_i18n}} elements_i18n')
						->where(
							['and', 'elements_i18n.locale = :locale', 'elements_i18n.uri = :uri'],
							[':locale' => $localeId, ':uri' => $sectionLocale->urlFormat]
						);

					if ($section->id)
					{
						$query->innerJoin('{{%entries}} entries', 'entries.id = elements_i18n.elementId')
							->andWhere('entries.sectionId != :sectionId', [':sectionId' => $section->id]);
					}

					if ($query->exists())
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

				if ($section->type == Section::TYPE_STRUCTURE && $section->maxLevels != 1)
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
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

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
					if ($section->type == Section::TYPE_STRUCTURE)
					{
						if (!$isNewSection && $oldSection->type == Section::TYPE_STRUCTURE)
						{
							$structure = Craft::$app->getStructures()->getStructureById($oldSection->structureId);
							$isNewStructure = false;
						}

						if (empty($structure))
						{
							$structure = new Structure();
							$isNewStructure = true;
						}

						$structure->maxLevels = $section->maxLevels;
						Craft::$app->getStructures()->saveStructure($structure);

						$sectionRecord->structureId = $structure->id;
						$section->structureId = $structure->id;
					}
					else
					{
						if (!$isNewSection && $oldSection->structureId)
						{
							// Delete the old one
							Craft::$app->getStructures()->deleteStructureById($oldSection->structureId);
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
						$oldSectionLocales = SectionLocaleRecord::find()
							->where(['sectionId' => $section->id])
							->indexBy('locale')
							->all();

						foreach ($oldSectionLocales as $key => $value)
						{
							$oldSectionLocales[$key] = SectionLocale::create($value);
						}
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
								Craft::$app->getDb()->createCommand()->update('{{%sections_i18n}}', [
									'enabledByDefault' => (int)$locale->enabledByDefault,
									'urlFormat'        => $locale->urlFormat,
									'nestedUrlFormat'  => $locale->nestedUrlFormat
								], [
									'id' => $oldLocale->id
								])->execute();
							}
						}
						else
						{
							$newLocaleData[] = [$section->id, $localeId, (int)$locale->enabledByDefault, $locale->urlFormat, $locale->nestedUrlFormat];
						}
					}

					// Insert the new locales
					Craft::$app->getDb()->createCommand()->batchInsert('{{%sections_i18n}}',
						['sectionId', 'locale', 'enabledByDefault', 'urlFormat', 'nestedUrlFormat'],
						$newLocaleData
					)->execute();

					if (!$isNewSection)
					{
						// Drop any locales that are no longer being used, as well as the associated entry/element locale
						// rows

						$droppedLocaleIds = array_diff(array_keys($oldSectionLocales), array_keys($sectionLocales));

						if ($droppedLocaleIds)
						{
							Craft::$app->getDb()->createCommand()->delete('{{%sections_i18n}}',
								['and', 'sectionId = :sectionId', ['in', 'locale', $droppedLocaleIds]],
								[':sectionId' => $section->id]
							)->execute();
						}
					}

					// Make sure there's at least one entry type for this section
					$entryTypeId = null;

					if (!$isNewSection)
					{
						// Let's grab all of the entry type IDs to save ourselves a query down the road if this is a Single
						$entryTypeIds = (new Query())
							->select('id')
							->from('{{%entrytypes}}')
							->where('sectionId = :sectionId', [':sectionId' => $section->id])
							->column();

						if ($entryTypeIds)
						{
							$entryTypeId = array_shift($entryTypeIds);
						}
					}

					if (!$entryTypeId)
					{
						$entryType = new EntryType();

						$entryType->sectionId = $section->id;
						$entryType->name = $section->name;
						$entryType->handle = $section->handle;

						if ($section->type == Section::TYPE_SINGLE)
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
						case Section::TYPE_SINGLE:
						{
							// In a nut, we want to make sure that there is one and only one Entry Type and Entry for this
							// section. We also want to make sure the entry has rows in the i18n tables
							// for each of the sections' locales.

							$singleEntryId = null;

							if (!$isNewSection)
							{
								// Make sure there's only one entry in this section
								$entryIds = (new Query())
									->select('id')
									->from('{{%entries}}')
									->where('sectionId = :sectionId', [':sectionId' => $section->id])
									->column();

								if ($entryIds)
								{
									$singleEntryId = array_shift($entryIds);

									// If there are any more, get rid of them
									if ($entryIds)
									{
										Craft::$app->getElements()->deleteElementById($entryIds);
									}

									// Make sure it's enabled and all that.

									Craft::$app->getDb()->createCommand()->update('{{%elements}}', [
										'enabled'  => 1,
										'archived' => 0,
									], [
										'id' => $singleEntryId
									])->execute();

									Craft::$app->getDb()->createCommand()->update('{{%entries}}', [
										'typeId'     => $entryTypeId,
										'authorId'   => null,
										'postDate'   => DateTimeHelper::currentTimeForDb(),
										'expiryDate' => null,
									], [
										'id' => $singleEntryId
									])->execute();
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

								Craft::$app->getDb()->createCommand()->insert('{{%elements}}', [
									'type' => Entry::className()
								])->execute();

								$singleEntryId = Craft::$app->getDb()->getLastInsertID();

								Craft::$app->getDb()->createCommand()->insert('{{%entries}}', [
									'id'        => $singleEntryId,
									'sectionId' => $section->id,
									'typeId'    => $entryTypeId,
									'postDate'  => DateTimeHelper::currentTimeForDb()
								])->execute();
							}

							break;
						}

						case Section::TYPE_STRUCTURE:
						{
							if (!$isNewSection && $isNewStructure)
							{
								// Add all of the entries to the structure
								$query = Entry::find()
									->locale(ArrayHelper::getFirstValue(array_keys($oldSectionLocales)))
									->sectionId($section->id)
									->status(null)
									->localeEnabled(false)
									->orderBy('elements.id');

								/** @var Entry $entry */
								foreach ($query->each() as $entry)
								{
									Craft::$app->getStructures()->appendToRoot($section->structureId, $entry, 'insert');
								}
							}

							break;
						}
					}

					// Finally, deal with the existing entries...

					if (!$isNewSection)
					{
						// Get the most-primary locale that this section was already enabled in
						$locales = array_values(array_intersect(Craft::$app->i18n->getSiteLocaleIds(), array_keys($oldSectionLocales)));

						if ($locales)
						{
							Craft::$app->getTasks()->queueTask([
								'type'        => ResaveElements::className(),
								'description' => Craft::t('app', 'Resaving {section} entries', ['section' => $section->name]),
								'elementType' => Entry::className(),
								'criteria'    => [
									'locale'        => $locales[0],
									'sectionId'     => $section->id,
									'status'        => null,
									'localeEnabled' => false,
									'limit'         => null,
								]
							]);
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

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Grab the entry ids so we can clean the elements table.
			$entryIds = (new Query())
				->select('id')
				->from('{{%entries}}')
				->where(['sectionId' => $sectionId])
				->column();

			Craft::$app->getElements()->deleteElementById($entryIds);

			// Delete the structure, if there is one
			$structureId = (new Query())
				->select('structureId')
				->from('{{%sections}}')
				->where(['id' => $sectionId])
				->scalar();

			if ($structureId)
			{
				Craft::$app->getStructures()->deleteStructureById($structureId);
			}

			// Delete the section.
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%sections}}', ['id' => $sectionId])->execute();

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
	 * @param Section $section
	 *
	 * @return bool
	 */
	public function isSectionTemplateValid(Section $section)
	{
		if ($section->hasUrls)
		{
			// Set Craft to the site template path
			$oldTemplatesPath = Craft::$app->getPath()->getTemplatesPath();
			Craft::$app->getPath()->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());

			// Does the template exist?
			$templateExists = Craft::$app->getView()->doesTemplateExist($section->template);

			// Restore the original template path
			Craft::$app->getPath()->setTemplatesPath($oldTemplatesPath);

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
		$entryTypes = EntryTypeRecord::find()
			->where(['sectionId' => $sectionId])
			->orderBy('sortOrder')
			->indexBy($indexBy)
			->all();

		foreach ($entryTypes as $key => $value)
		{
			$entryTypes[$key] = EntryType::create($value);
		}

		return $entryTypes;
	}

	/**
	 * Returns an entry type by its ID.
	 *
	 * @param int $entryTypeId
	 *
	 * @return EntryType|null
	 */
	public function getEntryTypeById($entryTypeId)
	{
		if (!isset($this->_entryTypesById) || !array_key_exists($entryTypeId, $this->_entryTypesById))
		{
			$entryTypeRecord = EntryTypeRecord::findOne($entryTypeId);

			if ($entryTypeRecord)
			{
				$this->_entryTypesById[$entryTypeId] = EntryType::create($entryTypeRecord);
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
		$entryTypes = EntryTypeRecord::findAll([
			'handle' => $entryTypeHandle
		]);

		foreach ($entryTypes as $key => $value)
		{
			$entryTypes[$key] = EntryType::create($value);
		}

		$entryTypes;
	}

	/**
	 * Saves an entry type.
	 *
	 * @param EntryType $entryType
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveEntryType(EntryType $entryType)
	{
		if ($entryType->id)
		{
			$entryTypeRecord = EntryTypeRecord::findOne($entryType->id);

			if (!$entryTypeRecord)
			{
				throw new Exception(Craft::t('app', 'No entry type exists with the ID “{id}”.', ['id' => $entryType->id]));
			}

			$isNewEntryType = false;
			$oldEntryType = EntryType::create($entryTypeRecord);
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
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

			try
			{
				// Fire a 'beforeSaveEntryType' event
				$event = new EntryTypeEvent([
					'entryType' => $entryType,
				]);

				$this->trigger(static::EVENT_BEFORE_SAVE_ENTRY_TYPE, $event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					if (!$isNewEntryType && $oldEntryType->fieldLayoutId)
					{
						// Drop the old field layout
						Craft::$app->getFields()->deleteLayoutById($oldEntryType->fieldLayoutId);
					}

					// Save the new one
					$fieldLayout = $entryType->getFieldLayout();
					Craft::$app->getFields()->saveLayout($fieldLayout);

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
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;

		try
		{
			foreach ($entryTypeIds as $entryTypeOrder => $entryTypeId)
			{
				$entryTypeRecord = EntryTypeRecord::findOne($entryTypeId);
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

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// Delete the field layout
			 $query = (new Query())
				->select('fieldLayoutId')
				->from('{{%entrytypes}}');

			if (is_array($entryTypeId))
			{
				$query->where(['in', 'id', $entryTypeId]);
			}
			else
			{
				$query->where(['id' => $entryTypeId]);
			}

			$fieldLayoutIds = $query->column();

			if ($fieldLayoutIds)
			{
				Craft::$app->getFields()->deleteLayoutById($fieldLayoutIds);
			}

			// Grab the entry IDs so we can clean the elements table.
			$query = (new Query())
				->select('id')
				->from('{{%entries}}');

			if (is_array($entryTypeId))
			{
				$query->where(['in', 'typeId', $entryTypeId]);
			}
			else
			{
				$query->where(['typeId' => $entryTypeId]);
			}

			$entryIds = $query->column();

			Craft::$app->getElements()->deleteElementById($entryIds);

			// Delete the entry type.
			if (is_array($entryTypeId))
			{
				$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%entrytypes}}', ['in', 'id', $entryTypeId])->execute();
			}
			else
			{
				$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%entrytypes}}', ['id' => $entryTypeId])->execute();
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
		$params     = [':type' => Section::TYPE_SINGLE, ':homeUri' => '__home__'];

		return (new Query())
			->from('{{%sections}} sections')
			->innerJoin('{{%sections_i18n}} sections_i18n', 'sections_i18n.sectionId = sections.id')
			->where($conditions, $params)
			->exists();
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
				$count = (new Query())
					->from('{{%sections}}')
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
	 * Returns a Query object prepped for retrieving sections.
	 *
	 * @return Query
	 */
	private function _createSectionQuery()
	{
		return (new Query())
			->select('sections.id, sections.structureId, sections.name, sections.handle, sections.type, sections.hasUrls, sections.template, sections.enableVersioning, structures.maxLevels')
			->leftJoin('{{%structures}} structures', 'structures.id = sections.structureId')
			->from('{{%sections}} sections')
			->orderBy('name');
	}
}
