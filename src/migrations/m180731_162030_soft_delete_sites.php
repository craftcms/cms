<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180731_162030_soft_delete_sites migration.
 */
class m180731_162030_soft_delete_sites extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the dateDeleted column
        $this->addColumn('{{%sites}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        $this->createIndex(null, '{{%sites}}', ['dateDeleted'], false);

        // Unique site handles should no longer be enforced by the DB
        MigrationHelper::dropIndexIfExists('{{%sites}}', ['handle'], true, $this);
        $this->createIndex(null, '{{%sites}}', ['handle'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180731_162030_soft_delete_sites cannot be reverted.\n";
        return false;
    }
}
