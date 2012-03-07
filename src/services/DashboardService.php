<?php
namespace Blocks;

/**
 *
 */
class DashboardService extends BaseComponent
{
	/**
	 * Returns the dashboard widgets for the current user
	 *
	 * @return array
	 */
	public function getUserWidgets()
	{
		$widgets = b()->db->createCommand()
			->from('widgets')
			->where('user_id = :id', array(':id' => b()->user->id))
			->order('sort_order')
			->queryAll();
		return Widget::model()->populateSubclassRecords($widgets);
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
			$widget = new Widget;
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
