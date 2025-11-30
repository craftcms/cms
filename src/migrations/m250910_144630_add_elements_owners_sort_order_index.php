<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m250910_144630_add_elements_owners_sort_order_index migration.
 */
class m250910_144630_add_elements_owners_sort_order_index extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createIndexIfMissing(Table::ELEMENTS_OWNERS, ['sortOrder'], false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropIndexIfExists(Table::ELEMENTS_OWNERS, ['sortOrder'], false);

        return true;
    }
}
