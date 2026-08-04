<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230524_000001_entry_type_icons migration.
 */
class m230524_000001_entry_type_icons extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRYTYPES, 'icon', $this->string()->after('handle'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230524_000001_entry_type_icons cannot be reverted.\n";
        return false;
    }
}
