<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

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
        $this->createIndex(null, Table::TEMPLATECACHES, ['expiryDate', 'cacheKey', 'siteId']);
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
