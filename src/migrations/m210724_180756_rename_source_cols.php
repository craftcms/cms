<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210724_180756_rename_source_cols migration.
 */
class m210724_180756_rename_source_cols extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->renameColumn(Table::DRAFTS, 'sourceId', 'canonicalId');
        $this->renameColumn(Table::REVISIONS, 'sourceId', 'canonicalId');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210724_180756_rename_source_cols cannot be reverted.\n";
        return false;
    }
}
