<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m260812_143000_drop_elementactivity_draftid_fk migration.
 */
class m260812_143000_drop_elementactivity_draftid_fk extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropForeignKeyIfExists(Table::ELEMENTACTIVITY, 'draftId');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->addForeignKey(null, Table::ELEMENTACTIVITY, ['draftId'], Table::DRAFTS, ['id'], 'CASCADE', null);
        return true;
    }
}
