<?php

namespace craft\app\migrations;

use craft\app\db\Migration;
use craft\app\helpers\MigrationHelper;

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
