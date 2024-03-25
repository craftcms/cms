<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240325_120656_add_is_primary_alt migration.
 */
class m240325_120656_add_is_primary_alt extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ASSETS_SITES, 'isPrimaryAlt', $this->boolean()->defaultValue(false)->after('alt'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240325_120656_add_is_primary_alt cannot be reverted.\n";
        return false;
    }
}
