<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151113_000000_adjust_image_widthheight extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		Craft::log('Adjusting width and height columns of the assetfiles table.', LogLevel::Info, true);

		$this->alterColumn('assetfiles', 'width', array('column' => ColumnType::Int));
		$this->alterColumn('assetfiles', 'height', array('column' => ColumnType::Int));

		Craft::log('Done adjusting width and height columns of the assetfiles table.', LogLevel::Info, true);

		return true;
	}
}
