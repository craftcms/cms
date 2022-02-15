<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m220214_000000_truncate_sessions migration.
 */
class m220214_000000_truncate_sessions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->truncateTable(Table::SESSIONS);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
