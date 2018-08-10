<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180731_162030_site_soft_deletes migration.
 */
class m180731_162030_site_soft_deletes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%sites}}', 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
        MigrationHelper::dropIndexIfExists('{{%sites}}', ['handle'], true, $this);
        $this->createIndex(null, '{{%sites}}', ['handle'], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180731_162030_site_soft_deletes cannot be reverted.\n";
        return false;
    }
}
