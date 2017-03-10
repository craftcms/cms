<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m150918_000001_add_colspan_to_widgets extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{

		// Allow transforms to have a format
		$this->addColumnAfter('widgets', 'colspan', array(AttributeType::Number, 'column' => ColumnType::TinyInt, 'unsigned' => true), 'sortOrder');

		return true;
	}
}
