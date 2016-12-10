<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m161109_000000_index_shuffle migration.
 */
class m161109_000000_index_shuffle extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Order is important
        Craft::info('Dropping `expiryDate,cacheKey,siteId,path` index on the templatecaches table.', __METHOD__);
        MigrationHelper::dropIndexIfExists('{{%templatecaches}}', 'expiryDate,cacheKey,siteId,path', false, $this);
        MigrationHelper::dropIndexIfExists('{{%templatecaches}}', 'siteId,cacheKey,path,expiryDate', false, $this);

        Craft::info('Creating `siteId,cacheKey,path,expiryDate` index on the templatecaches table.', __METHOD__);
        $this->createIndex($this->db->getIndexName('{{%templatecaches}}', 'siteId,cacheKey,path,expiryDate'), '{{%templatecaches}}', 'siteId,cacheKey,path,expiryDate');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161109_000000_index_shuffle cannot be reverted.\n";

        return false;
    }
}
