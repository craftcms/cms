<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190709_111144_nullable_revision_id migration.
 */
class m190709_111144_nullable_revision_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropForeignKeyIfExists(Table::DRAFTS, ['revisionId'], $this);
        $this->addForeignKey(null, Table::DRAFTS, ['revisionId'], Table::REVISIONS, ['id'], 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190709_111144_nullable_revision_id cannot be reverted.\n";
        return false;
    }
}
