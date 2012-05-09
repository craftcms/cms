<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends Component
{
	private $_allWidgets;

	/**
	 * Returns all installed widgets.
	 * @return array
	 */
	public function getAllWidgets()
	{
		if (!isset($this->_allWidgets))
			$this->_allWidgets = ComponentHelper::getComponents('widgets', 'Widget');

		return $this->_allWidgets;
	}

	/** 
	 * Returns a widget by its ID.
	 * @param int $widgetId
	 * @return Widget
	 */
	public function getWidgetById($widgetId)
	{
		$widget = b()->db->createCommand()
			->from('widgets')
			->where(array('id' => $widgetId, 'user_id' => b()->users->current->id))
			->queryRow();
		return Widget::model()->populateSubclassRecord($widget);
	}

	/**
	 * Returns the dashboard widgets for the current user.
	 * @return array
	 */
	public function getUserWidgets()
	{
		$widgets = b()->db->createCommand()
			->from('widgets')
			->where(array('user_id' => b()->users->current->id))
			->order('sort_order')
			->queryAll();
		return Widget::model()->populateSubclassRecords($widgets);
	}

	/**
	 * Assign the default widgets to a user.
	 * @param int $userId
	 */
	public function assignDefaultUserWidgets($userId = null)
	{
		if ($userId === null)
			$userId = 1;

		// Add the default dashboard widgets
		$widgets = array('Updates', 'RecentActivity', 'SiteMap', 'Feed');
		foreach ($widgets as $i => $widgetClass)
		{
			$widget = new Widget;
			$widget->user_id = $userId;
			$widget->class = $widgetClass;
			$widget->sort_order = ($i+1);
			$widget->save();
		}
	}

	/**
	 * Saves the user's dashboard settings
	 * @param array $settings
	 * @return bool
	 */
	public function saveSettings($settings)
	{
		// Get the current user
		$user = b()->users->getCurrent();
		if (!$user)
			throw new Exception('There is no current user.');

		$transaction = b()->db->beginTransaction();
		try
		{
			if (isset($settings['order']))
				$widgetIds = $settings['order'];
			else
			{
				$widgetIds = array_keys($settings);
				if (($deleteIndex = array_search('delete', $widgetIds)) !== false)
					array_splice($widgetIds, $deleteIndex, 1);
			}

			foreach ($widgetIds as $order => $widgetId)
			{
				$widgetData = $settings[$widgetId];

				$widget = new Widget;
				$isNewWidget = true;

				if (strncmp($widgetId, 'new', 3) != 0)
					$widget = $this->getWidgetById($widgetId);

				if (empty($widget))
					$widget = new Widget;

				$widget->user_id = $user->id;
				$widget->class = $widgetData['class'];
				$widget->sort_order = $order+1;
				$widget->save();

				if (!empty($widgetData['settings']))
					$widget->setSettings($widgetData['settings']);
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
}
