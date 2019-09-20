<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190416_014525_drop_unique_global_indexes migration.
 */
class m190416_014525_drop_unique_global_indexes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists(Table::GLOBALSETS, ['name'], true, $this);
        MigrationHelper::dropIndexIfExists(Table::GLOBALSETS, ['handle'], true, $this);

        $this->createIndex(null, Table::GLOBALSETS, ['name'], false);
        $this->createIndex(null, Table::GLOBALSETS, ['handle'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190416_014525_drop_unique_global_indexes cannot be reverted.\n";
        return false;
    }
}
