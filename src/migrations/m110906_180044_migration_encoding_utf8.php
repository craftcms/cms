<?php
namespace Blocks;

class m110906_180044_migration_encoding_utf8 extends \CDbMigration
{
	public function safeUp()
	{
		$dbName = Blocks::app()->config->getDbItem('database');
		$tablePrefix = Blocks::app()->config->getDbItem('tablePrefix');

		$this->execute('
			ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_migrations` DEFAULT CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;
			ALTER TABLE `'.$dbName.'`.`'.$tablePrefix.'_migrations` MODIFY COLUMN `version` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;
		');
	}

	public function safeDown()
	{
		echo "m110906_180044_migration_encoding_utf8 does not support migration down.\n";
		return false;
	}

}
