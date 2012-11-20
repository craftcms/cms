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
		return blx()->components->getComponentsByType(ComponentType::Widget);
	}

	/**
	 * Returns a widget type.
	 *
	 * @param string $class
	 * @return BaseWidget|null
	 */
	public function getWidgetType($class)
	{
		return blx()->components->getComponentByTypeAndClass(ComponentType::Widget, $class);
	}

	/**
	 * Populates a widget type.
	 *
	 * @param WidgetModel $widget
	 * @return BaseWidget|null
	 */
	public function populateWidgetType(WidgetModel $widget)
	{
		return blx()->components->populateComponentByTypeAndModel(ComponentType::Widget, $widget);
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

		return WidgetModel::populateModels($widgetRecords, 'id');
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
			return WidgetModel::populateModel($widgetRecord);
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

		$widgetType = $this->populateWidgetType($widget);
		$processedSettings = $widgetType->prepSettings($widget->settings);
		$widgetRecord->settings = $widget->settings = $processedSettings;
		$widgetType->setSettings($processedSettings);

		$recordValidates = $widgetRecord->validate();
		$settingsValidate = $widgetType->getSettings()->validate();

		if ($recordValidates && $settingsValidate)
		{
			if ($widgetRecord->isNewRecord())
			{
				$maxSortOrder = blx()->db->createCommand()
					->select('max(sortOrder)')
					->from('widgets')
					->queryScalar();

				$widgetRecord->sortOrder = $maxSortOrder + 1;
			}

			$widgetRecord->save(false);

			// Now that we have a widget ID, save it on the model
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
	 * @throws \Exception
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
		// Quick Post widget(s)
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			$sections = blx()->sections->findSections();

			foreach ($sections as $section)
			{
				$widget = new WidgetModel();
				$widget->type = 'QuickPost';
				$widget->settings = array(
					'section' => $section->id
				);

				$this->saveUserWidget($widget);
			}
		}
		else
		{
			$widget = new WidgetModel();
			$widget->type = 'QuickPost';
			$this->saveUserWidget($widget);
		}

		// Recent Entries widget
		$widget = new WidgetModel();
		$widget->type = 'RecentEntries';
		$this->saveUserWidget($widget);

		// Blog & Tonic feed widget
		$widget = new WidgetModel();
		$widget->type = 'Feed';
		$widget->settings = array(
			'url'   => 'http://feeds.feedburner.com/blogandtonic',
			'title' => 'Blog & Tonic'
		);
		$this->saveUserWidget($widget);

		// Get Help widget
		$widget = new WidgetModel();
		$widget->type = 'GetHelp';
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
			{
				$this->_noWidgetExists($widgetId);
			}
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

	/**
	 * Returns the items for the Feed widget.
	 *
	 * @param string $url
	 * @param int|null $limit
	 * @return array
	 */
	public function getFeedItems($url, $limit = 5)
	{
		$items = array();

		$feed = new \SimplePie();
		$feed->set_feed_url($url);
		$feed->set_cache_location(blx()->path->getCachePath());
		$feed->set_cache_duration(720);
		$feed->init();
		$feed->handle_content_type();

		foreach ($feed->get_items(0, $limit) as $item)
		{
			$date = new DateTime('@'.$item->get_date('U'));

			$items[] = array(
				'url'   => $item->get_permalink(),
				'title' => $item->get_title(),
				'date'  => $date->w3cDate()
			);
		}

		return $items;
	}
}
