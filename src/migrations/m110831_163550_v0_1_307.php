<?php

class m110831_163550_v0_1_307 extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable(Blocks::app()->config->getDatabaseTablePrefix().'_test', array(
			'id' => 'pk',
			'title' => 'string NOT NULL',
		));
	}

	public function safeDown()
	{
		$this->dropTable(Blocks::app()->config->getDatabaseTablePrefix().'_test');
	}
}
