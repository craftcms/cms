<?php

class m110911_054108_add_triggers_to_groups_table extends CDbMigration
{
	public function safeUp()
	{
		$dbName = Blocks::app()->config->databaseName;
		$tablePrefix = Blocks::app()->config->databaseTablePrefix;

		$this->execute('
			CREATE TRIGGER `AuditInfoInsert_Groups` BEFORE INSERT ON `'.$dbName.'`.`'.$tablePrefix.'_groups` FOR EACH ROW SET NEW.DateCreated = UTC_TIMESTAMP(), NEW.DateUpdated = UTC_TIMESTAMP(), NEW.Uid = UUID();
			CREATE TRIGGER `AuditInfoUpdate_Groups` BEFORE UPDATE ON `'.$dbName.'`.`'.$tablePrefix.'_groups` FOR EACH ROW SET NEW.DateUpdated = UTC_TIMESTAMP(), NEW DateCreated = OLD.DateCreated;
		');
	}

	public function safeDown()
	{

	}
}
