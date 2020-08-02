<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m200720_175543_drop_unique_constraints migration.
 */
class m200720_175543_drop_unique_constraints extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists(Table::ASSETTRANSFORMS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::ASSETTRANSFORMS, ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::FIELDGROUPS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::FIELDS, ['handle', 'context'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::MATRIXBLOCKTYPES, ['name', 'fieldId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::MATRIXBLOCKTYPES, ['handle', 'fieldId'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::USERGROUPS, ['handle'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::USERGROUPS, ['name'], true, $this);

        $this->createIndex(null, Table::ASSETTRANSFORMS, ['name']);
        $this->createIndex(null, Table::ASSETTRANSFORMS, ['handle']);
        $this->createIndex(null, Table::FIELDGROUPS, ['name']);
        $this->createIndex(null, Table::FIELDS, ['handle', 'context']);
        $this->createIndex(null, Table::MATRIXBLOCKTYPES, ['name', 'fieldId']);
        $this->createIndex(null, Table::MATRIXBLOCKTYPES, ['handle', 'fieldId']);
        $this->createIndex(null, Table::USERGROUPS, ['handle']);
        $this->createIndex(null, Table::USERGROUPS, ['name']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200720_175543_drop_unique_constraints cannot be reverted.\n";
        return false;
    }
}
