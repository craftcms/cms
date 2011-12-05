<?php

class CpService extends CApplicationComponent implements ICpService
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
		$userWidgets = UserWidgets::model()->findAllByAttributes(array('user_id' => $userId));
		$widgets = array();

		foreach ($userWidgets as $userWidget)
		{
			$widgetClass = $userWidget->type;
			$widgets[] = new $widgetClass($userWidget->id);
		}

		return $widgets;
	}
}
