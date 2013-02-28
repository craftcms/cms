<?php
namespace Craft;

/**
 *
 */
class GlobalsService extends BaseApplicationComponent
{
	/**
	 * Gets all global sets.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSets($indexBy = null)
	{
		$globalSetRecords = GlobalSetRecord::model()->with('element', 'element.i18n')->ordered()->findAll();
		return GlobalSetModel::populateModels($globalSetRecords, $indexBy);
	}

	/**
	 * Gets all global sets that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getEditableSets($indexBy = null)
	{
		$editableSets = array();
		$allSets = $this->getAllSets();

		foreach ($allSets as $globalSet)
		{
			if (craft()->userSession->checkPermission('editGlobalSet'.$globalSet->id))
			{
				if ($indexBy)
				{
					$editableSets[$globalSet->$indexBy] = $globalSet;
				}
				else
				{
					$editableSets[] = $globalSet;
				}
			}
		}

		return $editableSets;
	}

	/**
	 * Gets the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return GlobalSetRecord::model()->count();
	}

	/**
	 * Gets a global set by its ID.
	 *
	 * @param $globalSetId
	 * @return GlobalSetModel|null
	 */
	public function getSetById($globalSetId)
	{
		$globalSetRecord = GlobalSetRecord::model()->with('element')->findById($globalSetId);

		if ($globalSetRecord)
		{
			return GlobalSetModel::populateModel($globalSetRecord);
		}
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
