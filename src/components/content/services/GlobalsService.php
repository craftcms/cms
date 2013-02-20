<?php
namespace Blocks;

/**
 *
 */
class GlobalsService extends BaseApplicationComponent
{
	private $_globalContent;

	/**
	 * Gets the global content.
	 *
	 * @return EntryModel
	 */
	public function getGlobalContent()
	{
		if (!isset($this->_globalContent))
		{
			$record = EntryRecord::model()->findByAttributes(array(
				'type' => 'Globals'
			));

			if ($record)
			{
				$this->_globalContent = EntryModel::populateModel($record);
			}
			else
			{
				$this->_globalContent = new EntryModel();
			}
		}

		return $this->_globalContent;
	}

	/**
	 * Saves the global content.
	 *
	 * @param EntryModel $globals
	 * @return bool
	 */
	public function saveGlobalContent(EntryModel $globals)
	{
		if (!$globals->id)
		{
			// Create the entry record
			$entryRecord = new EntryRecord();
			$entryRecord->type = 'Globals';
			$entryRecord->save();

			// Now that we have the entry ID, save it on everything else
			$globals->id = $entryRecord->id;
		}

		$fieldLayout = blx()->fields->getLayoutByType('Globals');
		return blx()->entries->saveEntryContent($globals, $fieldLayout);
	}
}
