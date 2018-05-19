<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m150721_124739_templatecachequeries migration.
 */
class m150721_124739_templatecachequeries extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists('{{%templatecachequeries}}');

        // Delete all existing template caches
        $this->delete('{{%templatecaches}}');

        // templatecachecriteria => templatecachequeries
        MigrationHelper::renameTable('{{%templatecachecriteria}}', '{{%templatecachequeries}}', $this);
        MigrationHelper::renameColumn('{{%templatecachequeries}}', 'criteria', 'query', $this);
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
