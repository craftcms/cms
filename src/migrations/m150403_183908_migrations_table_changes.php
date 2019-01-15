<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\MigrationManager;
use craft\db\Table;
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
        if (!$this->db->columnExists(Table::MIGRATIONS, 'name')) {
            MigrationHelper::renameColumn(Table::MIGRATIONS, 'version', 'name', $this);
        }

        if (!$this->db->columnExists(Table::MIGRATIONS, 'type')) {
            $values = [
                MigrationManager::TYPE_APP,
                MigrationManager::TYPE_PLUGIN,
                MigrationManager::TYPE_CONTENT,
            ];
            $this->addColumn(Table::MIGRATIONS, 'type', $this->enum('type', $values)->after('pluginId')->notNull()->defaultValue(MigrationManager::TYPE_APP));
            $this->createIndex(null, Table::MIGRATIONS, ['type', 'pluginId']);
        }

        MigrationHelper::dropIndexIfExists(Table::MIGRATIONS, ['name'], true, $this);

        $this->update(Table::MIGRATIONS, ['type' => 'plugin'], ['not', ['pluginId' => null]]);
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
