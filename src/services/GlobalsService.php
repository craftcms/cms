<?php
namespace Craft;

/**
 *
 */
class GlobalsService extends BaseApplicationComponent
{
	private $_globalContent;

	/**
	 * Gets the global content.
	 *
	 * @return ElementModel
	 */
	public function getGlobalContent()
	{
		if (!isset($this->_globalContent))
		{
			$record = ElementRecord::model()->findByAttributes(array(
				'type' => ElementType::Globals
			));

			if ($record)
			{
				$this->_globalContent = ElementModel::populateModel($record);
			}
			else
			{
				$this->_globalContent = new ElementModel();
			}
		}

		return $this->_globalContent;
	}

	/**
	 * Saves the global content.
	 *
	 * @param ElementModel $globals
	 * @return bool
	 */
	public function saveGlobalContent(ElementModel $globals)
	{
		if (!$globals->id)
		{
			// Create the entry record
			$elementRecord = new ElementRecord();
			$elementRecord->type = ElementType::Globals;
			$elementRecord->save();

			// Now that we have the entry ID, save it on everything else
			$globals->id = $elementRecord->id;
		}

		$fieldLayout = craft()->fields->getLayoutByType(ElementType::Globals);
		return craft()->elements->saveElementContent($globals, $fieldLayout);
	}
}
