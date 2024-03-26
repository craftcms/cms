<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m230904_190356_address_fields migration.
 */
class m230904_190356_address_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->renameColumn(Table::ADDRESSES, 'ownerId', 'primaryOwnerId');
        $this->addColumn(Table::ADDRESSES, 'fieldId', $this->integer()->after('primaryOwnerId'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230904_190356_address_fields cannot be reverted.\n";
        return false;
    }
}
