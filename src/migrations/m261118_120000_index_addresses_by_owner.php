<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m261118_120000_index_addresses_by_owner migration.
 */
class m261118_120000_index_addresses_by_owner extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createIndexIfMissing(Table::ADDRESSES, ['primaryOwnerId'], false);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m261118_120000_index_addresses_by_owner cannot be reverted.\n";
        return false;
    }
}
