<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m210209_135503_soft_delete_field_groups migration.
 */
class m210209_135503_soft_delete_field_groups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn(Table::FIELDGROUPS, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, Table::FIELDGROUPS, ['dateDeleted', 'name'], false);

        // Unique group names should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists(Table::FIELDGROUPS, ['name'], true, $this);
        if (!MigrationHelper::doesIndexExist(Table::FIELDGROUPS, ['name'], false, $this->db)) {
            $this->createIndex(null, Table::FIELDGROUPS, ['name'], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210209_135503_soft_delete_field_groups cannot be reverted.\n";
        return false;
    }
}
