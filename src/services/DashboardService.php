<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends ApplicationComponent
{
	/**
	 * Returns all installed widgets.
	 *
	 * @return array
	 */
	public function getAllWidgets()
	{
		return blx()->components->getComponentsByType('widget');
	}

	/**
	 * Returns a widget by its class.
	 *
	 * @param string $class
	 * @return mixed
	 */
	public function getWidgetByClass($class)
	{
		return blx()->components->getComponentByTypeAndClass('widget', $class);
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return Widget
	 */
	public function getWidgetById($id)
	{
		$record = WidgetRecord::model()->findByAttributes(array(
			'id' => $id,
			'userId' => blx()->accounts->getCurrentUser()->id
		));

		if ($record)
			return $this->populateWidget($record);
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 *
	 * @return array
	 */
	public function getUserWidgets()
	{
		$records = WidgetRecord::model()->ordered()->findAllByAttributes(array(
			'userId' => blx()->accounts->getCurrentUser()->id
		));

		return $this->populateWidgets($records);
	}

	/**
	 * Populates a widget with a given record.
	 *
	 * @param WidgetRecord $record
	 * @return BaseWidget
	 */
	public function populateWidget(WidgetRecord $record)
	{
		return blx()->components->populateComponent('widget', $record);
	}

	/**
	 * Creates an array of widgets based on an array of widget records.
	 *
	 * @param array $records
	 * @return array
	 */
	public function populateWidgets($records)
	{
		return blx()->components->populateComponents('widget', $records);
	}

	/**
	 * Saves a widget.
	 *
	 * @param array    $settings
	 * @param int|null $widgetId
	 * @return BaseWidget
	 */
	public function saveWidget($settings, $widgetId = null)
	{
		$record = $this->_getWidgetRecord($widgetId);

		$record->class    = $settings['class'];
		$record->settings = (!empty($settings['settings']) ? $settings['settings'] : null);

		$widget = $this->populateWidget($record);

		$recordValidates = $record->validate();
		$settingsValidate = $widget->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// The widget might have tweaked the settings
			$record->settings = $widget->getSettings()->getAttributes();

			if ($record->isNewRecord())
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from('widgets')
					->queryScalar();

				$record->sortOrder = $maxSortOrder + 1;
			}

			$record->save(false);
		}

		return $widget;
	}

	/**
	 * Deletes a widget.
	 *
	 * @param int $widgetId
	 */
	public function deleteWidget($widgetId)
	{
		$record = $this->_getWidgetRecord($widgetId);
		$record->delete();
	}

	/**
	 * Reorders widgets.
	 *
	 * @param array $widgetIds
	 */
	public function reorderWidgets($widgetIds)
	{
		$transaction = blx()->db->beginTransaction();

		try
		{
			foreach ($widgetIds as $widgetOrder => $widgetId)
			{
				$record = $this->_getWidgetRecord($widgetId);
				$record->sortOrder = $widgetOrder+1;
				$record->save();
			}

			$transaction->commit();
		}
		catch (\Exception $e)
		{
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Assign the default widgets to a user.
	 *
	 * @param int $userId
	 * @throws Exception
	 * @return bool
	 */
	public function assignDefaultUserWidgets($userId)
	{
		if (!$userId)
			throw new Exception(Blocks::t('Missing userID in {methodName}', array('methodName' => __METHOD__)));

		$success = true;

		// Add the default dashboard widgets
		$widgets = array('RecentActivity', 'Feed');
		foreach ($widgets as $i => $widgetClass)
		{
			$widget = new WidgetRecord();
			$widget->userId = $userId;
			$widget->class = $widgetClass;
			$widget->sortOrder = ($i + 1);
			$widget->save();

			if ($widget->hasErrors())
			{
				$success = false;
				$errors = $widget->getErrors();
				$errorMessages = implode('.  ', $errors);
				Blocks::log('There was a problem assigning the widget “{widgetClass}” to userID “{userId}”: {errorMessages}', array('widgetClass' => $widgetClass, 'userId' => $userId, 'errorMessages' => $errorMessages), \CLogger::LEVEL_ERROR);
			}
		}

		return $success;
	}

	/**
	 * Gets a widget's record.
	 *
	 * @access private
	 * @param int $widgetId
	 * @return WidgetRecord
	 */
	private function _getWidgetRecord($widgetId = null)
	{
		$userId = blx()->accounts->getCurrentUser()->id;

		if ($widgetId)
		{
			$record = WidgetRecord::model()->findByAttributes(array(
				'id'     => $widgetId,
				'userId' => $userId
			));

			// This is serious business.
			if (!$record)
				$this->_noWidgetExists($widgetId);
		}
		else
		{
			$record = new WidgetRecord();
			$record->userId = $userId;
		}

		return $record;
	}

	/**
	 * Throws a "No widget exists" exception.
	 *
	 * @access private
	 * @param int $widgetId
	 * @throws Exception
	 */
	private function _noWidgetExists($widgetId)
	{
		throw new Exception(Blocks::t('No widget exists with the ID “{id}”', array('id' => $widgetId)));
	}
}
