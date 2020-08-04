<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m150403_183908_migrations_table_changes migration.
 */
class m150403_183908_migrations_table_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists(Table::MIGRATIONS, 'name')) {
            MigrationHelper::renameColumn(Table::MIGRATIONS, 'version', 'name', $this);
        }

        if (!$this->db->columnExists(Table::MIGRATIONS, 'type')) {
            $values = ['app', 'plugin', 'content'];
            $this->addColumn(Table::MIGRATIONS, 'type', $this->enum('type', $values)->after('pluginId')->notNull()->defaultValue('app'));
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
