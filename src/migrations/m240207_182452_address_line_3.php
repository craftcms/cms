<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m240207_182452_address_line_3 migration.
 */
class m240207_182452_address_line_3 extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ADDRESSES, 'addressLine3', $this->string()->after('addressLine2'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240207_182452_address_line_3 cannot be reverted.\n";
        return false;
    }
}
