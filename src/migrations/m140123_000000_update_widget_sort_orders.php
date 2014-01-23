<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m140123_000000_update_widget_sort_orders extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		craft()->config->maxPowerCaptain();

		$widgets = craft()->db->createCommand()
			->select('id, userId, sortOrder')
			->from('widgets')
			->order('sortOrder')
			->queryAll();

		$totalWidgetsPerUser = array();

		foreach ($widgets as $widget)
		{
			if (!isset($totalWidgetsPerUser[$widget['userId']]))
			{
				$totalWidgetsPerUser[$widget['userId']] = 1;
			}
			else
			{
				$totalWidgetsPerUser[$widget['userId']]++;
			}

			if ($widget['sortOrder'] != $totalWidgetsPerUser[$widget['userId']])
			{
				$this->update('widgets', array('sortOrder' => $totalWidgetsPerUser[$widget['userId']]), array('id' => $widget['id']));
			}
		}

		return true;
	}
}
