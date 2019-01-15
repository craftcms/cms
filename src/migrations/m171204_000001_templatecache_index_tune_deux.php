<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
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
        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'siteId'], false, $this);
        $this->createIndex(null, Table::TEMPLATECACHES, ['cacheKey', 'siteId', 'expiryDate']);

        MigrationHelper::dropIndexIfExists(Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'siteId', 'path'], false, $this);
        $this->createIndex(null, Table::TEMPLATECACHES, ['cacheKey', 'siteId', 'expiryDate', 'path']);
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
