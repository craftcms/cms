<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends BaseApplicationComponent
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
	 * @return BaseWidget|null
	 */
	public function getWidgetByClass($class)
	{
		return blx()->components->getComponentByTypeAndClass('widget', $class);
	}

	/**
	 * Populates a widget.
	 *
	 * @param WidgetPackage $widgetPackage
	 * @return BaseWidget|null
	 */
	public function populateWidget(WidgetPackage $widgetPackage)
	{
		return blx()->components->populateComponentByTypeAndPackage('widget', $widgetPackage);
	}

	/**
	 * Populates a widget package.
	 *
	 * @param array|WidgetRecord $attributes
	 * @return WidgetPackage
	 */
	public function populateWidgetPackage($attributes)
	{
		if ($attributes instanceof WidgetRecord)
		{
			$attributes = $attributes->getAttributes();
		}

		$widgetPackage = new WidgetPackage();

		$widgetPackage->id = $attributes['id'];
		$widgetPackage->class = $attributes['class'];
		$widgetPackage->settings = $attributes['settings'];

		return $widgetPackage;
	}

	/**
	 * Mass-populates widget packages.
	 *
	 * @param array $data
	 * @return array
	 */
	public function populateWidgetPackages($data)
	{
		$widgetPackages = array();

		foreach ($data as $attributes)
		{
			$widgetPackages[] = $this->populateWidgetPackage($attributes);
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
			'userId' => blx()->accounts->getCurrentUser()->id
		));

		return $this->populateWidgetPackages($widgetRecords);
	}

	/**
	 * Returns a widget by its ID.
	 *
	 * @param int $id
	 * @return WidgetPackage
	 */
	public function getUserWidgetById($id)
	{
		$widgetRecord = WidgetRecord::model()->findByAttributes(array(
			'id' => $id,
			'userId' => blx()->accounts->getCurrentUser()->id
		));

		if ($widgetRecord)
		{
			return $this->populateWidgetPackage($widgetRecord);
		}
	}

	/**
	 * Saves a widget for the current user.
	 *
	 * @param WidgetPackage $widgetPackage
	 * @return bool
	 */
	public function saveUserWidget(WidgetPackage $widgetPackage)
	{
		$widgetRecord = $this->_getUserWidgetRecordById($widgetPackage->id);

		$widgetRecord->class = $widgetPackage->class;
		$widgetRecord->settings = $widgetPackage->settings;

		$widget = $this->populateWidget($widgetPackage);

		$recordValidates = $widgetRecord->validate();
		$settingsValidate = $widget->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			// Set the record settings now that the widget has had a chance to tweak them
			$widgetRecord->settings = $widget->getSettings()->getAttributes();

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
			if (!$widgetPackage->id)
			{
				$widgetPackage->id = $widgetRecord->id;
			}

			return true;
		}
		else
		{
			$widgetPackage->errors = $widgetRecord->getErrors();
			$widgetPackage->settingsErrors = $widget->getSettings()->getErrors();

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
		$widgetPackage = new WidgetPackage();

		$widgetPackage->class = 'Feed';
		$widgetPackage->settings = array(
			'url'   => 'http://feeds.feedburner.com/blogandtonic',
			'title' => 'Blog & Tonic'
		);

		$this->saveUserWidget($widgetPackage);
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
		$userId = blx()->accounts->getCurrentUser()->id;

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
