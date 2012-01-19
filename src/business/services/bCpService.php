<?php

/**
 *
 */
class bCpService extends CApplicationComponent
{
	/*
	 * Dashboard
	 */

	/**
	 * Returns the dashboard widgets for the current user
	 * @return array
	 */
	public function getDashboardWidgets()
	{
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
}
