<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230928_144045_add_subpath_to_volumes migration.
 */
class m230928_144045_add_subpath_to_volumes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::VOLUMES, 'subpath', $this->string()->after('fs'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230928_144045_add_subpath_to_volumes cannot be reverted.\n";
        return false;
    }
}
