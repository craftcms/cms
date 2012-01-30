<?php
namespace Blocks;

class m110831_163550_v0_1_307 extends \CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{Test}}', array(
			'id' => 'pk',
			'title' => 'string NOT NULL',
		));
	}

	public function safeDown()
	{
		$this->dropTable('{{Test}}');
	}
}
