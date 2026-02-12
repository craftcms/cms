<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230616_183820_remove_field_name_limit migration.
 */
class m230616_183820_remove_field_name_limit extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn(Table::FIELDS, 'name', $this->text()->notNull());
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230616_183820_remove_field_name_limit cannot be reverted.\n";
        return false;
    }
}
