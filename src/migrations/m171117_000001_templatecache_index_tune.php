<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m171117_000001_templatecache_index_tune migration.
 */
class m171117_000001_templatecache_index_tune extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createIndex(null, '{{%templatecaches}}', ['expiryDate', 'cacheKey', 'siteId']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171117_000001_templatecache_index_tune cannot be reverted.\n";
        return false;
    }
}
