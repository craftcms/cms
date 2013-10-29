<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m131105_000006_remove_gethelp_widget_for_non_admins extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Get all of the offending widget IDs
		$widgetIds = craft()->db->createCommand()
			->select("w.id")
			->from("widgets w")
			->join("users u", "w.userId = u.id")
			->where("u.admin is not null")
			->where("u.admin = 0 AND w.type = 'GetHelp'")
			->queryAll();

		if ($widgetIds)
		{
			Craft::log('Found '.count($widgetIds).' Get Help widgets that should not be there.', LogLevel::Info, true);
			$this->delete('widgets', array('in', 'id', $widgetIds));
			Craft::log('Widgets deleted.', LogLevel::Info, true);
		}
		else
		{
			Craft::log('No offending Get Help widgets.', LogLevel::Info, true);
		}

		return true;
	}
}
