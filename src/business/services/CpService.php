<?php
namespace Blocks;

/**
 *
 */
class CpService extends \CApplicationComponent
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
		$userWidgets = UserWidget::model()->findAllByAttributes(array('user_id' => $userId));
		$widgets = array();

		foreach ($userWidgets as $userWidget)
		{
			// TODO: Check on active record lazy loading.
			//$widgetClass = $userWidget->plugin !== null ? $userWidget->plugin->name.'\\'.$userWidget->class : __NAMESPACE__.'\\'.$userWidget->class;
			$widgetClass = __NAMESPACE__.'\\'.$userWidget->class;
			$widgets[] = new $widgetClass($userWidget->id);
		}

		return $widgets;
	}
}
