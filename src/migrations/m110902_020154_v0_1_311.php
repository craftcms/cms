<?php

class m110902_020154_v0_1_311 extends CDbMigration
{
	public function safeUp()
	{
		$this->delete(Blocks::app()->config->getDatabaseTablePrefix().'_test');
	}

	public function safeDown()
	{
		echo "m110902_020154_remove_test_table does not support migration down.\n";
		return false;
	}
}
