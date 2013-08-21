<?php
namespace Craft;

/**
 *
 */
class SectionsService extends BaseApplicationComponent
{
	private $_allSectionIds;
	private $_editableSectionIds;

	private $_sectionsById;
	private $_fetchedAllSections = false;

	private $_entryTypesById;

	/**
	 * Returns all of the section IDs.
	 *
	 * @return array
	 */
	public function getAllSectionIds()
	{
		if (!isset($this->_allSectionIds))
		{
			$query = craft()->db->createCommand()
				->select('id')
				->from('sections');

			if (!Craft::hasPackage(CraftPackage::PublishPro))
			{
				$query->limit(1);
			}

			$this->_allSectionIds = $query->queryColumn();
		}

		return $this->_allSectionIds;
	}

	/**
	 * Returns all of the section IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableSectionIds()
	{
		if (!isset($this->_editableSectionIds))
		{
			$this->_editableSectionIds = array();
			$allSectionIds = $this->getAllSectionIds();

			foreach ($allSectionIds as $sectionId)
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
	 * @return array
	 */
	public function getAllSections($indexBy = null)
	{
		if (!$this->_fetchedAllSections)
		{
			$criteria = new \CDbCriteria();

			if (!Craft::hasPackage(CraftPackage::PublishPro))
			{
				$criteria->limit = 1;
			}

			$sectionRecords = SectionRecord::model()->ordered()->findAll($criteria);
			$this->_sectionsById = SectionModel::populateModels($sectionRecords, 'id');
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
	 * @return array
	 */
	public function getEditableSections($indexBy = null)
	{
		$sections = $this->getAllSections();
		$editableSectionIds = $this->getEditableSectionIds();
		$editableSections = array();

		foreach ($sections as $section)
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
	 * @param $sectionId
	 * @return SectionModel|null
	 */
	public function getSectionById($sectionId)
	{
		if (!isset($this->_sectionsById) || !array_key_exists($sectionId, $this->_sectionsById))
		{
			$sectionRecord = SectionRecord::model()->findById($sectionId);

			if ($sectionRecord)
			{
				$this->_sectionsById[$sectionId] = SectionModel::populateModel($sectionRecord);
			}
			else
			{
				$this->_sectionsById[$sectionId] = null;
			}
		}

		return $this->_sectionsById[$sectionId];
	}

	/**
	 * Gets a section by its handle.
	 *
	 * @param string $sectionHandle
	 * @return SectionModel|null
	 */
	public function getSectionByHandle($sectionHandle)
	{
		$sectionRecord = SectionRecord::model()->findByAttributes(array(
			'handle' => $sectionHandle
		));

		if ($sectionRecord)
		{
			return SectionModel::populateModel($sectionRecord);
		}
	}

	/**
	 * Returns a section's locales.
	 *
	 * @param int $sectionId
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getSectionLocales($sectionId, $indexBy = null)
	{
		$records = SectionLocaleRecord::model()->findAllByAttributes(array(
			'sectionId' => $sectionId
		));

		return SectionLocaleModel::populateModels($records, $indexBy);
	}

	/**
	 * Saves a section.
	 *
	 * @param SectionModel $section
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSection(SectionModel $section)
	{
		if ($section->id)
		{
			$sectionRecord = SectionRecord::model()->findById($section->id);

			if (!$sectionRecord)
			{
				throw new Exception(Craft::t('No section exists with the ID “{id}”', array('id' => $sectionId)));
			}

			$isNewSection = false;
			$oldSection = SectionModel::populateModel($sectionRecord);
		}
		else
		{
			$sectionRecord = new SectionRecord();
			$isNewSection = true;
		}

		$sectionRecord->name    = $section->name;
		$sectionRecord->handle  = $section->handle;
		$sectionRecord->hasUrls = $section->hasUrls;

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
		foreach ($section->getLocales() as $localeId => $sectionLocale)
		{
			if ($section->hasUrls)
			{
				$errorKey = 'urlFormat-'.$localeId;

				if (empty($sectionLocale->urlFormat))
				{
					$section->addError($errorKey, Craft::t('{attribute} cannot be blank.', array('attribute' => 'URL Format')));
				}
				else if (strpos($sectionLocale->urlFormat, '{slug}') === false)
				{
					$section->addError($errorKey, Craft::t('URL Format must contain “{slug}”'));
				}
			}
			else
			{
				$sectionLocale->urlFormat = null;
			}
		}

		if (!$section->hasErrors())
		{
			$transaction = craft()->db->beginTransaction();
			try
			{
				$sectionRecord->save(false);

				// Now that we have a section ID, save it on the model
				if (!$section->id)
				{
					$section->id = $sectionRecord->id;
				}

				// Might as well update our cache of the section while we have it.
				// (It's possilbe that the URL format includes {section.handle} or something...)
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

				foreach ($section->getLocales() as $localeId => $locale)
				{
					$updateEntries = false;

					// Was this already selected?
					if (!$isNewSection && isset($oldSectionLocales[$localeId]))
					{
						$oldLocale = $oldSectionLocales[$localeId];

						// Has the URL format changed?
						if ($locale->urlFormat != $oldLocale->urlFormat)
						{
							craft()->db->createCommand()->update('sections_i18n',
								array('urlFormat' => $locale->urlFormat),
								array('id' => $oldLocale->id)
							);

							$updateEntries = true;
						}
					}
					else
					{
						$newLocaleData[] = array($section->id, $localeId, $locale->urlFormat);

						if (!$isNewSection)
						{
							$updateEntries = true;
						}
					}

					if ($updateEntries && $section->hasUrls)
					{
						// This may take a while...
						set_time_limit(120);

						// Fetch all the entries in this section
						$entries = craft()->elements->getCriteria(ElementType::Entry, array(
							'sectionId' => $section->id,
							'locale'    => $localeId,
							'limit'     => null,
						))->find();

						foreach ($entries as $entry)
						{
							$uri = craft()->templates->renderObjectTemplate($locale->urlFormat, $entry);

							if ($uri != $entry->uri)
							{
								craft()->db->createCommand()->update('elements_i18n',
									array('uri' => $uri),
									array('elementId' => $entry->id, 'locale' => $localeId)
								);
							}
						}
					}
				}

				// Insert the new locales
				craft()->db->createCommand()->insertAll('sections_i18n', array('sectionId', 'locale', 'urlFormat'), $newLocaleData);

				if (!$isNewSection)
				{
					// Drop the old ones
					$disabledLocaleIds = array_diff(array_keys($oldSectionLocales), array_keys($section->getLocales()));
					foreach ($disabledLocaleIds as $localeId)
					{
						craft()->db->createCommand()->delete('sections_i18n', array('id' => $oldSectionLocales[$localeId]->id));
					}

					// Drop the old entry URIs if the section no longer has URLs
					if (!$section->hasUrls && $oldSection->hasUrls)
					{
						// Clear out all the URIs
						$entryIds = craft()->db->createCommand()
							->select('id')
							->from('entries')
							->where(array('sectionId' => $section->id))
							->queryColumn();

						craft()->db->createCommand()->update('elements_i18n',
							array('uri' => null),
							array('in', 'elementId', $entryIds)
						);
					}
				}

				// Create an entry type if this is a brand new section
				if ($isNewSection)
				{
					$entryType = new EntryTypeModel();

					$entryType->sectionId  = $section->id;
					$entryType->name       = $section->name;
					$entryType->handle     = $section->handle;
					$entryType->titleLabel = Craft::t('Title');

					$this->saveEntryType($entryType);
				}

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a section by its ID.
	 *
	 * @param int $sectionId
	 * @throws \Exception
	 * @return bool
	*/
	public function deleteSectionById($sectionId)
	{
		if (!$sectionId)
		{
			return false;
		}

		$transaction = craft()->db->beginTransaction();
		try
		{
			// Grab the entry ids so we can clean the elements table.
			$entryIds = craft()->db->createCommand()
				->select('id')
				->from('entries')
				->where(array('sectionId' => $sectionId))
				->queryColumn();

			craft()->elements->deleteElementById($entryIds);

			// Delete the section.
			$affectedRows = craft()->db->createCommand()->delete('sections', array('id' => $sectionId));

			$transaction->commit();

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	// Entry types

	/**
	 * Returns a section's entry types.
	 *
	 * @param int $sectionId
	 * @param string|null $indexBy
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
	 * Saves an entry type.
	 *
	 * @param EntryTypeModel $entryType
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
				throw new Exception(Craft::t('No entry type exists with the ID “{id}”', array('id' => $entryTypeId)));
			}

			$isNewEntryType = false;
			$oldEntryType = EntryTypeModel::populateModel($entryTypeRecord);
		}
		else
		{
			$entryTypeRecord = new EntryTypeRecord();
			$isNewEntryType = true;
		}

		$entryTypeRecord->sectionId  = $entryType->sectionId;
		$entryTypeRecord->name       = $entryType->name;
		$entryTypeRecord->handle     = $entryType->handle;
		$entryTypeRecord->titleLabel = $entryType->titleLabel;

		$entryTypeRecord->validate();
		$entryType->addErrors($entryTypeRecord->getErrors());

		if (!$entryType->hasErrors())
		{
			$transaction = craft()->db->beginTransaction();
			try
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

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes an entry type(s) by its ID.
	 *
	 * @param int|array $entryTypeId
	 * @throws \Exception
	 * @return bool
	*/
	public function deleteEntryTypeById($entryTypeId)
	{
		if (!$entryTypeId)
		{
			return false;
		}

		$transaction = craft()->db->beginTransaction();
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

			$transaction->commit();

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}
}
