<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m190605_223807_unsaved_drafts migration.
 */
class m190605_223807_unsaved_drafts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsPgsql()) {
            $this->execute('alter table ' . Table::DRAFTS . ' alter column [[sourceId]] drop not null');
            $this->execute('alter table ' . Table::DRAFTS . ' alter column [[revisionId]] drop not null');
        } else {
            $this->alterColumn(Table::DRAFTS, 'sourceId', $this->integer());
            $this->alterColumn(Table::DRAFTS, 'revisionId', $this->integer());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190605_223807_unsaved_drafts cannot be reverted.\n";
        return false;
    }
}
