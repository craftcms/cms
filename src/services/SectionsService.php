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
		// If we've already fetched all sections we can save ourselves a trip to the DB
		// for section IDs that don't exist
		if (!$this->_fetchedAllSections &&
			(!isset($this->_sectionsById) || !array_key_exists($sectionId, $this->_sectionsById))
		)
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

		if (isset($this->_sectionsById[$sectionId]))
		{
			return $this->_sectionsById[$sectionId];
		}
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
	 * Gets a section record or creates a new one.
	 *
	 * @access private
	 * @param int $sectionId
	 * @throws Exception
	 * @return SectionRecord
	 */
	private function _getSectionRecordById($sectionId = null)
	{
		if ($sectionId)
		{
			$sectionRecord = SectionRecord::model()->findById($sectionId);

			if (!$sectionRecord)
			{
				throw new Exception(Craft::t('No section exists with the ID “{id}”', array('id' => $sectionId)));
			}
		}
		else
		{
			$sectionRecord = new SectionRecord();
		}

		return $sectionRecord;
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
		$sectionRecord = $this->_getSectionRecordById($section->id);

		$isNewSection = $sectionRecord->isNewRecord();

		if (!$isNewSection)
		{
			$oldSection = SectionModel::populateModel($sectionRecord);
		}

		$sectionRecord->name       = $section->name;
		$sectionRecord->handle     = $section->handle;
		$sectionRecord->titleLabel = $section->titleLabel;
		$sectionRecord->hasUrls    = $section->hasUrls;

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
				if (!$isNewSection && $oldSection->fieldLayoutId)
				{
					// Drop the old field layout
					craft()->fields->deleteLayoutById($oldSection->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $section->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Update the section record/model with the new layout ID
				$section->fieldLayoutId = $fieldLayout->id;
				$sectionRecord->fieldLayoutId = $fieldLayout->id;

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
			// Delete the field layout
			$fieldLayoutId = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('sections')
				->where(array('id' => $sectionId))
				->queryScalar();

			if ($fieldLayoutId)
			{
				craft()->fields->deleteLayoutById($fieldLayoutId);
			}

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
}
