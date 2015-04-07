<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\MigrationHelper;

/**
 * m150403_183908_migrations_table_changes migration.
 */
class m150403_183908_migrations_table_changes extends Migration
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function safeUp()
	{
		if (!$this->db->columnExists('{{%migrations}}', 'name'))
		{
			MigrationHelper::renameColumn('{{%migrations}}', 'version', 'name', $this);
		}

		if (!$this->db->columnExists('{{%migrations}}', 'type'))
		{
			$this->addColumnAfter('{{%migrations}}', 'type', "enum('app', 'plugin', 'content') NOT NULL DEFAULT 'app'", 'pluginId');

			$this->createIndex(
				$this->db->getIndexName('{{%migrations}}', 'type,pluginId'),
				'{{%migrations}}',
				'type,pluginId'
			);
		}

		MigrationHelper::dropIndexIfExists('{{%migrations}}', ['name'], true, $this);

		$this->update('{{%migrations}}', ['type' => 'plugin'], 'pluginId is not null');
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown()
	{
		echo "m150403_183908_migrations_table_changes cannot be reverted.\n";
		return false;
	}
}
