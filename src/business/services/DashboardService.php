<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends \CApplicationComponent
{
	/**
	 * Returns the dashboard widgets for the current user
	 *
	 * @param null $userId
	 * @return array
	 */
	public function getUserWidgets($userId = null)
	{
		if ($userId === null)
			$userId = 1;

		$userWidgets = UserWidget::model()->with('plugin')->findAllByAttributes(array('user_id' => $userId));
		$widgets = array();

		foreach ($userWidgets as $userWidget)
		{
			$widgetClass = __NAMESPACE__.'\\'.$userWidget->class;
			$widgets[] = new $widgetClass($userWidget->id);
		}

		return $widgets;
	}

	/**
	 * Assign the default widgets to a user
	 *
	 * @param null $userId
	 */
	public function assignDefaultUserWidgets($userId = null)
	{
		if ($userId === null)
			$userId = 1;

		// Add the default dashboard widgets
		$widgets = array('UpdatesWidget', 'RecentActivityWidget', 'SiteMapWidget', 'FeedWidget');
		foreach ($widgets as $i => $widgetClass)
		{
			$widget = new UserWidget;
			$widget->user_id = $userId;
			$widget->class = $widgetClass;
			$widget->sort_order = ($i+1);
			$widget->save();
		}
	}
}
