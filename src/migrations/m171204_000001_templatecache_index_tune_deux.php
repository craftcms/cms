<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m171204_000001_templatecache_index_tune_deux migration.
 */
class m171204_000001_templatecache_index_tune_deux extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists('{{%templatecaches}}', ['expiryDate', 'cacheKey', 'siteId'], false, $this);
        $this->createIndex(null, '{{%templatecaches}}', ['cacheKey', 'siteId', 'expiryDate']);

        MigrationHelper::dropIndexIfExists('{{%templatecaches}}', ['expiryDate', 'cacheKey', 'siteId', 'path'], false, $this);
        $this->createIndex(null, '{{%templatecaches}}', ['cacheKey', 'siteId', 'expiryDate', 'path']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171204_000001_templatecache_index_tune_deux cannot be reverted.\n";
        return false;
    }
}
