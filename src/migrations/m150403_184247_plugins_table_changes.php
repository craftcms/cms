<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\MigrationHelper;
use yii\db\Expression;

/**
 * m150403_184247_plugins_table_changes migration.
 */
class m150403_184247_plugins_table_changes extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		if ($this->db->columnExists('{{%plugins}}', 'class'))
		{
			MigrationHelper::renameColumn('{{%plugins}}', 'class', 'handle', $this);
		}

		$this->update('{{%plugins}}', [
			'handle' => new Expression('LOWER(`handle`)')
		]);

		MigrationHelper::dropIndexIfExists('{{%plugins}}', ['handle'], true, $this);

		$this->createIndex(
			$this->db->getIndexName('{{%plugins}}', 'handle', true),
			'{{%plugins}}',
			'handle',
			true
		);
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150403_184247_plugins_table_changes cannot be reverted.\n";
		return false;
	}
}
