<?php

namespace craft\migrations;

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
        echo "    > Dropping `expiryDate,cacheKey,siteId,path` index on the templatecaches table.\n";
        MigrationHelper::dropIndexIfExists('{{%templatecaches}}', 'expiryDate,cacheKey,siteId,path', false, $this);
        MigrationHelper::dropIndexIfExists('{{%templatecaches}}', 'siteId,cacheKey,path,expiryDate', false, $this);

        echo "    > Creating `siteId,cacheKey,path,expiryDate` index on the templatecaches table.\n";
        $this->createIndex(null, '{{%templatecaches}}', ['siteId', 'cacheKey', 'path', 'expiryDate']);
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
