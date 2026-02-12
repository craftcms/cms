<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230524_000002_entry_type_colors migration.
 */
class m230524_000002_entry_type_colors extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRYTYPES, 'color', $this->string()->after('icon'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230524_000002_entry_type_colors cannot be reverted.\n";
        return false;
    }
}
