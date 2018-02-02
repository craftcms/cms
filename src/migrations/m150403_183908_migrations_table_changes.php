<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\MigrationManager;
use craft\helpers\MigrationHelper;

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
        if (!$this->db->columnExists('{{%migrations}}', 'name')) {
            MigrationHelper::renameColumn('{{%migrations}}', 'version', 'name', $this);
        }

        if (!$this->db->columnExists('{{%migrations}}', 'type')) {
            $values = [
                MigrationManager::TYPE_APP,
                MigrationManager::TYPE_PLUGIN,
                MigrationManager::TYPE_CONTENT,
            ];
            $this->addColumn('{{%migrations}}', 'type', $this->enum('type', $values)->after('pluginId')->notNull()->defaultValue(MigrationManager::TYPE_APP));
            $this->createIndex(null, '{{%migrations}}', ['type', 'pluginId']);
        }

        MigrationHelper::dropIndexIfExists('{{%migrations}}', ['name'], true, $this);

        $this->update('{{%migrations}}', ['type' => 'plugin'], ['not', ['pluginId' => null]]);
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
