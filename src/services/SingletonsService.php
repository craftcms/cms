<?php
namespace Blocks;

/**
 *
 */
class SingletonsService extends BaseApplicationComponent
{
	/**
	 * Gets all singletons.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSingletons($indexBy = null)
	{
		$singletonRecords = SingletonRecord::model()->with('entry', 'entry.i18n')->ordered()->findAll();
		return SingletonModel::populateModels($singletonRecords, $indexBy);
	}

	/**
	 * Gets all singletons that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSingletons($indexBy = null)
	{
		$editableSingletons = array();
		$allSingletons = $this->getAllSingletons();

		foreach ($allSingletons as $singleton)
		{
			if (blx()->userSession->checkPermission('editSingleton'.$singleton->id))
			{
				if ($indexBy)
				{
					$editableSingletons[$singleton->$indexBy] = $singleton;
				}
				else
				{
					$editableSingletons[] = $singleton;
				}
			}
		}

		return $editableSingletons;
	}

	/**
	 * Gets the total number of singletons.
	 *
	 * @return int
	 */
	public function getTotalSingletons()
	{
		return SingletonRecord::model()->count();
	}

	/**
	 * Gets a singleton by its ID.
	 *
	 * @param $singletonId
	 * @return SingletonModel|null
	 */
	public function getSingletonById($singletonId)
	{
		$singletonRecord = SingletonRecord::model()->with('entry')->findById($singletonId);

		if ($singletonRecord)
		{
			return SingletonModel::populateModel($singletonRecord);
		}
	}

	/**
	 * Gets a singleton by its URI.
	 *
	 * @param string $uri
	 * @return SingletonModel|null
	 */
	public function getSingletonByUri($uri)
	{
		$singletonRecord = SingletonRecord::model()->with('entry')->findByAttributes(array(
			'uri' => $uri
		));

		if ($singletonRecord)
		{
			return SingletonModel::populateModel($singletonRecord);
		}
	}

	/**
	 * Returns a singleton's locales.
	 *
	 * @param int $singletonId
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getSingletonLocales($singletonId, $indexBy = null)
	{
		$records = SingletonLocaleRecord::model()->findAllByAttributes(array(
			'singletonId' => $singletonId
		));

		return SingletonLocaleModel::populateModels($records, $indexBy);
	}

	/**
	 * Saves a singleton.
	 *
	 * @param SingletonModel $singleton
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSingleton(SingletonModel $singleton)
	{
		$singletonRecord = $this->_getSingletonRecordById($singleton->id);

		$isNewSingleton = $singletonRecord->isNewRecord();

		if (!$isNewSingleton)
		{
			$oldSingleton = SingletonModel::populateModel($singletonRecord);
		}

		$singletonRecord->name     = $singleton->name;
		$singletonRecord->template = $singleton->template;

		$singletonRecord->validate();
		$singleton->addErrors($singletonRecord->getErrors());

		// Make sure that all of the URIs are set properly
		$i18nRecords = array();

		foreach ($singleton->getLocales() as $localeId => $singletonLocale)
		{
			$i18nRecord = null;

			if ($singleton->id)
			{
				$i18nRecord = blx()->entries->getEntryLocalizationRecord($singleton->id, $localeId);
			}

			if (!$i18nRecord)
			{
				$i18nRecord = new EntryLocalizationRecord();
				$i18nRecord->entryId = $singleton->id;
				$i18nRecord->locale  = $localeId;
			}

			$i18nRecord->title = $singleton->name;
			$i18nRecord->uri   = $singletonLocale->getUri();

			$i18nRecord->validate();
			$singleton->addLocaleErrors($i18nRecord->getErrors(), $localeId);

			$i18nRecords[] = $i18nRecord;
		}

		if (!$singleton->hasErrors())
		{
			if (!$isNewSingleton && $oldSingleton->fieldLayoutId)
			{
				// Drop the old field layout
				blx()->fields->deleteLayoutById($oldSingleton->fieldLayoutId);
			}

			// Save the new one
			$fieldLayout = $singleton->getFieldLayout();
			blx()->fields->saveLayout($fieldLayout);

			// Update the singleton record/model with the new layout ID
			$singleton->fieldLayoutId = $fieldLayout->id;
			$singletonRecord->fieldLayoutId = $fieldLayout->id;

			if ($isNewSingleton)
			{
				// Create the entry record
				$entryRecord = new EntryRecord();
				$entryRecord->type = 'Singleton';
				$entryRecord->save();

				// Now that we have the entry ID, save it on everything else
				$singleton->id = $entryRecord->id;
				$singletonRecord->id = $entryRecord->id;

				foreach ($i18nRecords as $i18nRecord)
				{
					$i18nRecord->entryId = $entryRecord->id;
				}
			}

			$singletonRecord->save(false);

			foreach ($i18nRecords as $i18nRecord)
			{
				$i18nRecord->save(false);
			}

			// Update the singletonlocales table
			$newLocaleData = array();

			if (!$isNewSingleton)
			{
				// Get the old singleton locales
				$oldSingletonLocaleRecords = SingletonLocaleRecord::model()->findAllByAttributes(array(
					'singletonId' => $singleton->id
				));
				$oldSingletonLocales = SingletonLocaleModel::populateModels($oldSingletonLocaleRecords, 'locale');
			}

			foreach ($singleton->getLocales() as $localeId => $locale)
			{
				// Is this a new selection?
				if ($isNewSingleton || !isset($oldSingletonLocales[$localeId]))
				{
					$newLocaleData[] = array($singleton->id, $localeId);
				}
			}

			// Insert the new locales
			blx()->db->createCommand()->insertAll('singletonlocales', array('singletonId', 'locale'), $newLocaleData);

			if (!$isNewSingleton)
			{
				// Drop the old ones
				$disabledLocaleIds = array_diff(array_keys($oldSingletonLocales), array_keys($singleton->getLocales()));
				foreach ($disabledLocaleIds as $localeId)
				{
					blx()->db->createCommand()->delete('singletonlocales', array('id' => $oldSingletonLocales[$localeId]->id));
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Saves a singleton's content
	 *
	 * @param SingletonModel $singleton
	 * @return bool
	 */
	public function saveContent(SingletonModel $singleton)
	{
		return blx()->entries->saveEntryContent($singleton, $singleton->getFieldLayout());
	}

	/**
	 * Deletes a singleton by its ID.
	 *
	 * @param int $singletonId
	 * @return bool
	*/
	public function deleteSingletonById($singletonId)
	{
		blx()->db->createCommand()->delete('singletons', array('id' => $singletonId));
		return true;
	}

	/**
	 * Gets a singleton record or creates a new one.
	 *
	 * @access private
	 * @param int $singletonId
	 * @throws Exception
	 * @return SingletonRecord
	 */
	private function _getSingletonRecordById($singletonId = null)
	{
		if ($singletonId)
		{
			$singletonRecord = SingletonRecord::model()->with('entry')->findById($singletonId);

			if (!$singletonRecord)
			{
				throw new Exception(Blocks::t('No singleton exists with the ID “{id}”', array('id' => $singletonId)));
			}
		}
		else
		{
			$singletonRecord = new SingletonRecord();
		}

		return $singletonRecord;
	}

	/**
	 * Gets a singleton content record by the singleton ID, or creates a new one.
	 *
	 * @param int $singletonId
	 * @return SingletonContentRecord
	 */
	public function getSingletonContentRecordBySingletonId($singletonId)
	{
		$record = SingletonContentRecord::model()->findByAttributes(array(
			'singletonId'   => $singletonId,
			'language' => Blocks::getLanguage(),
		));

		if (empty($record))
		{
			$record = new SingletonContentRecord();
			$record->singletonId = $singletonId;
			$record->language = Blocks::getLanguage();
		}

		return $record;
	}
}
