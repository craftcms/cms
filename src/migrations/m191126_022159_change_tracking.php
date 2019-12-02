<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m191126_022159_change_tracking migration.
 */
class m191126_022159_change_tracking extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists(Table::CHANGEDATTRIBUTES);
        $this->createTable(Table::CHANGEDATTRIBUTES, [
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'attribute' => $this->string()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'propagated' => $this->boolean()->notNull(),
            'userId' => $this->integer(),
            'PRIMARY KEY([[elementId]], [[siteId]], [[attribute]])',
        ]);
        $this->addForeignKey(null, Table::CHANGEDATTRIBUTES, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDATTRIBUTES, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDATTRIBUTES, ['userId'], Table::USERS, ['id'], 'SET NULL', 'CASCADE');

        $this->dropTableIfExists(Table::CHANGEDFIELDS);
        $this->createTable(Table::CHANGEDFIELDS, [
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'fieldId' => $this->integer()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'propagated' => $this->boolean()->notNull(),
            'userId' => $this->integer(),
            'PRIMARY KEY([[elementId]], [[siteId]], [[fieldId]])',
        ]);
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['fieldId'], Table::FIELDS, ['id'], 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, Table::CHANGEDFIELDS, ['userId'], Table::USERS, ['id'], 'SET NULL', 'CASCADE');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if ($this->db->tableExists(Table::CHANGEDATTRIBUTES)) {
            MigrationHelper::dropTable(Table::CHANGEDATTRIBUTES, $this);
        }

        if ($this->db->tableExists(Table::CHANGEDFIELDS)) {
            MigrationHelper::dropTable(Table::CHANGEDFIELDS, $this);
        }
    }
}
