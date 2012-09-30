<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends BaseApplicationComponent
{
	/**
	 * Returns all installed widget types.
	 *
	 * @return array
	 */
	public function getAllWidgetTypes()
	{
		return blx()->components->getComponentsByType('widget');
	}

	/**
	 * Returns a widget type.
	 *
	 * @param string $class
	 * @return BaseWidget|null
	 */
	public function getWidgetType($class)
	{
		return blx()->components->getComponentByTypeAndClass('widget', $class);
	}

	/**
	 * Populates a widget type.
	 *
	 * @param WidgetModel $widget
	 * @return BaseWidget|null
	 */
	public function populateWidgetType(WidgetModel $widget)
	{
		return blx()->components->populateComponentByTypeAndPackage('widget', $widget);
	}

	/**
	 * Populates a widget package.
	 *
	 * @param array|WidgetRecord $attributes
	 * @return WidgetModel
	 */
	public function populateWidget($attributes)
	{
		if ($attributes instanceof WidgetRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$widget = new WidgetModel();

		$widget->id = $attributes['id'];
		$widget->type = $attributes['type'];
		$widget->settings = $attributes['settings'];

		return $widget;
	}

	/**
	 * Mass-populates widget packages.
	 *
	 * @param array  $data
	 * @param string $index
	 * @return array
	 */
	public function populateWidgets($data, $index = 'id')
	{
		$widgetPackages = array();

		foreach ($data as $attributes)
		{
			$widget = $this->populateWidget($attributes);
			$widgetPackages[$widget->$index] = $widget;
		}

		return $widgetPackages;
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 *
	 * @return array
	 */
	public function getUserWidgets()
	{
		$widgetRecords = WidgetRecord::model()->ordered()->findAllByAttributes(array(
			'userId' => blx()->account->getCurrentUser()->id
		));

		return $this->populateWidgets($widgetRecords);
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return WidgetModel
	 */
	public function getUserWidgetById($id)
	{
		$widgetRecord = WidgetRecord::model()->findByAttributes(array(
			'id' => $id,
			'userId' => blx()->account->getCurrentUser()->id
		));

		if ($widgetRecord)
		{
			return $this->populateWidget($widgetRecord);
		}
	}

	/**
	 * Saves a widget for the current user.
	 *
	 * @param WidgetModel $widget
	 * @return bool
	 */
	public function saveUserWidget(WidgetModel $widget)
	{
		$widgetRecord = $this->_getUserWidgetRecordById($widget->id);

		$widgetRecord->type = $widget->type;
		$widgetRecord->settings = $widget->settings;

		$widgetType = $this->populateWidgetType($widget);

		$recordValidates = $widgetRecord->validate();
		$settingsValidate = $widgetType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the widget has had a chance to tweak them
			$widgetRecord->settings = $widgetType->getSettings()->getAttributes();

			if ($widgetRecord->isNewRecord())
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from('widgets')
					->queryScalar();

				$widgetRecord->sortOrder = $maxSortOrder + 1;
			}

			$widgetRecord->save(false);

			// Now that we have a widget ID, save it on the package
			if (!$widget->id)
			{
				$widget->id = $widgetRecord->id;
			}

			return true;
		}
		else
		{
			$widget->addErrors($widgetRecord->getErrors());
			$widget->addSettingErrors($widgetType->getSettings()->getErrors());

			return false;
		}
	}

	/**
	 * Deletes a widget.
	 *
	 * @param int $widgetId
	 * @return bool
	 */
	public function deleteUserWidgetById($widgetId)
	{
		$widgetRecord = $this->_getUserWidgetRecordById($widgetId);
		$widgetRecord->delete();

		return true;
	}

	/**
	 * Reorders widgets.
	 *
	 * @param array $widgetIds
	 * @return bool
	 */
	public function reorderUserWidgets($widgetIds)
	{
		$transaction = blx()->db->beginTransaction();

		try
		{
			foreach ($widgetIds as $widgetOrder => $widgetId)
			{
				$widgetRecord = $this->_getUserWidgetRecordById($widgetId);
				$widgetRecord->sortOrder = $widgetOrder+1;
				$widgetRecord->save();
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

	/**
	 * Adds the default widgets to the logged-in user.
	 *
	 * @return bool
	 */
	public function addDefaultUserWidgets()
	{
		$widget = new WidgetModel();

		$widget->type = 'Feed';
		$widget->settings = array(
			'url'   => 'http://feeds.feedburner.com/blogandtonic',
			'title' => 'Blog & Tonic'
		);

		$this->saveUserWidget($widget);
	}

	/**
	 * Gets a widget's record.
	 *
	 * @access private
	 * @param int $widgetId
	 * @return WidgetRecord
	 */
	private function _getUserWidgetRecordById($widgetId = null)
	{
		$userId = blx()->account->getCurrentUser()->id;

		if ($widgetId)
		{
			$widgetRecord = WidgetRecord::model()->findByAttributes(array(
				'id'     => $widgetId,
				'userId' => $userId
			));

			if (!$widgetRecord)
				$this->_noWidgetExists($widgetId);
		}
		else
		{
			$widgetRecord = new WidgetRecord();
			$widgetRecord->userId = $userId;
		}

		return $widgetRecord;
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
