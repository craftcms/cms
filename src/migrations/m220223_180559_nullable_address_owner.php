<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220223_180559_nullable_address_owner migration.
 */
class m220223_180559_nullable_address_owner extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->getIsPgsql()) {
            $this->execute(sprintf('alter table %s alter column [[ownerId]] drop not null', Table::ADDRESSES));
        } else {
            $this->alterColumn(Table::ADDRESSES, 'ownerId', $this->integer());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220223_180559_nullable_address_owner cannot be reverted.\n";
        return false;
    }
}
