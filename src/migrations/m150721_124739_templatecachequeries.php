<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m150721_124739_templatecachequeries migration.
 */
class m150721_124739_templatecachequeries extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists(Table::TEMPLATECACHEQUERIES);

        // Delete all existing template caches
        $this->delete(Table::TEMPLATECACHES);

        // templatecachecriteria => templatecachequeries
        MigrationHelper::renameTable('{{%templatecachecriteria}}', Table::TEMPLATECACHEQUERIES, $this);
        MigrationHelper::renameColumn(Table::TEMPLATECACHEQUERIES, 'criteria', 'query', $this);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150721_124739_templatecachequeries cannot be reverted.\n";

        return false;
    }
}
