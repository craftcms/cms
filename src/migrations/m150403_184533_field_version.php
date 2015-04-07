<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;

/**
 * m150403_184533_field_version migration.
 */
class m150403_184533_field_version extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		if (!$this->db->columnExists('{{%info}}', 'fieldVersion'))
		{
			$this->addColumnAfter('{{%info}}', 'fieldVersion', 'integer not null default \'1\'', 'track');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150403_184533_field_version cannot be reverted.\n";
		return false;
	}
}
