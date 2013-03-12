<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m130312_122359_transform_tweaks extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		$this->alterColumn('assettransforms', 'width', 'INT(10) NULL');
		$this->alterColumn('assettransforms', 'height', 'INT(10) NULL');
		$this->alterColumn('assettransforms', 'mode', array('column' => ColumnType::Char, 'length' => 7, 'required' => true, 'default' => 'crop'));

		$this->update('assettransforms', array('mode' => 'fit'), 'mode = "scaleTo"');
		$this->update('assettransforms', array('mode' => 'crop'), 'mode = "scaleAnd"');
		$this->update('assettransforms', array('mode' => 'stretch'), 'mode = "stretch"');

		return true;
	}
}
