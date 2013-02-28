<?php
namespace Craft;

/**
 *
 */
class GlobalsService extends BaseApplicationComponent
{
	private $_allGlobalSetIds;
	private $_editableGlobalSetIds;
	private $_globalSetsById;

	/**
	 * Returns all of the global set IDs.
	 *
	 * @return array
	 */
	public function getAllSetIds()
	{
		if (!isset($this->_allGlobalSetIds))
		{
			$this->_allGlobalSetIds = craft()->db->createCommand()
				->select('id')
				->from('globalsets')
				->queryColumn();
		}

		return $this->_allGlobalSetIds;
	}

	/**
	 * Returns all of the global set IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableSetIds()
	{
		if (!isset($this->_editableGlobalSetIds))
		{
			$this->_editableGlobalSetIds = array();
			$allGlobalSetIds = $this->getAllSetIds();

			foreach ($allGlobalSetIds as $globalSetId)
			{
				if (craft()->userSession->checkPermission('editGlobalSet'.$globalSetId))
				{
					$this->_editableGlobalSetIds[] = $globalSetId;
				}
			}
		}

		return $this->_editableGlobalSetIds;
	}

	/**
	 * Gets all global sets.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSets($indexBy = null)
	{
		if (!isset($this->_globalSetsById))
		{
			$globalSetRecords = GlobalSetRecord::model()->with('element', 'element.i18n')->ordered()->findAll();
			$this->_globalSetsById = GlobalSetModel::populateModels($globalSetRecords, 'id');
		}

		if ($indexBy == 'id')
		{
			$globalSets = $this->_globalSetsById;
		}
		else if (!$indexBy)
		{
			$globalSets = array_values($this->_globalSetsById);
		}
		else
		{
			$globalSets = array();
			foreach ($this->_globalSetsById as $globalSet)
			{
				$globalSets[$globalSet->$indexBy] = $globalSet;
			}
		}

		return $globalSets;
	}

	/**
	 * Gets all global sets that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSets($indexBy = null)
	{
		$editableGlobalSetIds = $this->getEditableSetIds();
		$globalSets = $this->getAllSets('id');
		$editableGlobalSets = array();

		foreach ($editableGlobalSetIds as $globalSetId)
		{
			if (isset($globalSets[$globalSetId]))
			{
				$globalSet = $globalSets[$globalSetId];

				if ($indexBy)
				{
					$editableGlobalSets[$globalSet->$indexBy] = $globalSet;
				}
				else
				{
					$editableGlobalSets[] = $globalSet;
				}
			}
		}

		return $editableGlobalSets;
	}

	/**
	 * Gets the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return count($this->getAllSetIds());
	}

	/**
	 * Gets the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return count($this->getEditableSetIds());
	}

	/**
	 * Gets a global set by its ID.
	 *
	 * @param $globalSetId
	 * @return GlobalSetModel|null
	 */
	public function getSetById($globalSetId)
	{
		if (!isset($this->_globalSetsById) || !array_key_exists($globalSetId, $this->_globalSetsById))
		{
			$globalSetRecord = GlobalSetRecord::model()->findById($globalSetId);

			if ($globalSetRecord)
			{
				$this->_globalSetsById[$globalSetId] = GlobalSetModel::populateModel($globalSetRecord);
			}
			else
			{
				$this->_globalSetsById[$globalSetId] = null;
			}
		}

		return $this->_globalSetsById[$globalSetId];
	}

	/**
	 * Saves a global set.
	 *
	 * @param GlobalSetModel $globalSet
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSet(GlobalSetModel $globalSet)
	{
		$globalSetRecord = $this->_getSetRecordById($globalSet->id);

		$isNewSet = $globalSetRecord->isNewRecord();

		if (!$isNewSet)
		{
			$oldSet = GlobalSetModel::populateModel($globalSetRecord);
		}

		$globalSetRecord->name   = $globalSet->name;
		$globalSetRecord->handle = $globalSet->handle;

		$globalSetRecord->validate();
		$globalSet->addErrors($globalSetRecord->getErrors());

		if (!$globalSet->hasErrors())
		{
			if (!$isNewSet && $oldSet->fieldLayoutId)
			{
				// Drop the old field layout
				craft()->fields->deleteLayoutById($oldSet->fieldLayoutId);
			}

			// Save the new one
			$fieldLayout = $globalSet->getFieldLayout();
			craft()->fields->saveLayout($fieldLayout, false);

			// Update the set record/model with the new layout ID
			$globalSet->fieldLayoutId = $fieldLayout->id;
			$globalSetRecord->fieldLayoutId = $fieldLayout->id;

			if ($isNewSet)
			{
				// Create the element record
				$elementRecord = new ElementRecord();
				$elementRecord->type = ElementType::GlobalSet;
				$elementRecord->save();

				// Now that we have the element ID, save it on everything else
				$globalSet->id = $elementRecord->id;
				$globalSetRecord->id = $elementRecord->id;
			}

			$globalSetRecord->save(false);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Saves a global set's content
	 *
	 * @param GlobalSetModel $globalSet
	 * @return bool
	 */
	public function saveContent(GlobalSetModel $globalSet)
	{
		return craft()->elements->saveElementContent($globalSet, $globalSet->getFieldLayout());
	}

	/**
	 * Gets a global set record or creates a new one.
	 *
	 * @access private
	 * @param int $globalSetId
	 * @throws Exception
	 * @return GlobalSetRecord
	 */
	private function _getSetRecordById($globalSetId = null)
	{
		if ($globalSetId)
		{
			$globalSetRecord = GlobalSetRecord::model()->with('element')->findById($globalSetId);

			if (!$globalSetRecord)
			{
				throw new Exception(Craft::t('No global set exists with the ID “{id}”', array('id' => $globalSetId)));
			}
		}
		else
		{
			$globalSetRecord = new GlobalSetRecord();
		}

		return $globalSetRecord;
	}
}
