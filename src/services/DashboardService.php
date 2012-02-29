<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends BaseService
{
	/**
	 * Returns the dashboard widgets for the current user
	 *
	 * @return array
	 */
	public function getWidgets()
	{
		$userWidgets = UserWidget::model()->with('plugin')->findAllByAttributes(array(
			'user_id' => Blocks::app()->user->id
		));
		$widgets = array();

		foreach ($userWidgets as $widget)
		{
			$widgetClass = __NAMESPACE__.'\\'.$widget->class.'Widget';

			if ($widget->plugin)
			{
				$path = Blocks::app()->path->pluginsPath.$widget->plugin->class.'/widgets'.'/'.$widget->class.'Widget.php';
				require_once Blocks::app()->path->pluginsPath.$widget->plugin->class.'/widgets'.'/'.$widget->class.'Widget.php';
			}

			$widgets[] = new $widgetClass($widget->id);
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
		$widgets = array('Updates', 'RecentActivity', 'SiteMap', 'Feed');
		foreach ($widgets as $i => $widgetClass)
		{
			$widget = new UserWidget;
			$widget->user_id = $userId;
			$widget->class = $widgetClass;
			$widget->sort_order = ($i+1);
			$widget->save();
		}
	}

	/**
	 * TEMPORARY -- this will be replaced by the global notification service
	 * @return array
	 */
	public function getAlerts()
	{
		return DashboardHelper::getAlerts();
	}
}
