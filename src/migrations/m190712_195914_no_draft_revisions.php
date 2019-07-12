<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190712_195914_no_draft_revisions migration.
 */
class m190712_195914_no_draft_revisions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists(Table::DRAFTS, 'revisionId')) {
            MigrationHelper::dropForeignKeyIfExists(Table::DRAFTS, ['revisionId'], $this);
            $this->dropColumn(Table::DRAFTS, 'revisionId');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190712_195914_no_draft_revisions cannot be reverted.\n";
        return false;
    }
}
