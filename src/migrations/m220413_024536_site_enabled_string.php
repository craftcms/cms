<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220413_024536_site_enabled_string migration.
 */
class m220413_024536_site_enabled_string extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn(Table::SITES, 'enabled', $this->string()->notNull()->defaultValue('true'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220413_024536_site_enabled_string cannot be reverted.\n";
        return false;
    }
}
