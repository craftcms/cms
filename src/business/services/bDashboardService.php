<?php

/**
 *
 */
class bDashboardService extends CApplicationComponent
{
	/**
	 * Returns the dashboard widgets for the current user
	 * @return array
	 */
	public function getUserWidgets($userId = null)
	{
		if ($userId === null)
			$userId = 1;

		$userWidgets = bUserWidget::model()->findAllByAttributes(array('user_id' => $userId));
		$widgets = array();

		foreach ($userWidgets as $userWidget)
		{
			$widgetClass = $userWidget->class;
			$widgets[] = new $widgetClass($userWidget->id);
		}

		return $widgets;
	}

	/**
	 * Assign the default widgets to a user
	 */
	public function assignDefaultUserWidgets($userId = null)
	{
		if ($userId === null)
			$userId = 1;

		// Add the default dashboard widgets
		$widgets = array('bUpdatesWidget', 'bRecentActivityWidget', 'bSiteMapWidget', 'bFeedWidget');
		foreach ($widgets as $i => $widgetClass)
		{
			$widget = new bUserWidget;
			$widget->user_id = $userId;
			$widget->class = $widgetClass;
			$widget->sort_order = ($i+1);
			$widget->save();
		}
	}
}
