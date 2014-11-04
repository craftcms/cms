<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m141024_000001_field_layout_tabs extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adding tags to all field layouts...', LogLevel::Info, true);

		// Get the field layouts that don't have any tabs
		$layoutIds = craft()->db->createCommand()
			->select('l.id')
			->from('fieldlayouts l')
			->leftJoin('fieldlayouttabs t', 't.layoutId = l.id')
			->where('t.id is null')
			->queryColumn();

		foreach ($layoutIds as $layoutId)
		{
			// Create a Content tab
			$this->insert('fieldlayouttabs', array(
				'layoutId' => $layoutId,
				'name' => 'Content',
				'sortOrder' => 1
			));

			// Get its ID
			$tabId = craft()->db->getLastInsertID();

			// Assign its fields to that tab
			$this->update('fieldlayoutfields', array(
				'tabId' => $tabId
			), array(
				'layoutId' => $layoutId
			));
		}

		// Damn you MySQL 5.6!
		$this->dropForeignKey('fieldlayoutfields', 'tabId');

		// Make the tabId column required
		$this->alterColumn('fieldlayoutfields', 'tabId', array(ColumnType::Int, 'null' => false));

		$this->addForeignKey('fieldlayoutfields', 'tabId', 'fieldlayouttabs', 'id', 'CASCADE');

		Craft::log('Done adding tabs to all field layouts.', LogLevel::Info, true);

		return true;
	}
}
