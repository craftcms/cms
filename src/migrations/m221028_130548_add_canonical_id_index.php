<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m221028_130548_add_canonical_id_index migration.
 */
class m221028_130548_add_canonical_id_index extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createIndexIfMissing(Table::ELEMENTS, ['canonicalId'], false);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropIndexIfExists(Table::ELEMENTS, ['canonicalId'], false);
        return true;
    }
}
