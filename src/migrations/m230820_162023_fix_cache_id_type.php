<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m230820_162023_fix_cache_id_type migration.
 */
class m230820_162023_fix_cache_id_type extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (Craft::$app->getDb()->tableExists(Table::CACHE)) {
            $this->alterColumn(Table::CACHE, 'id', $this->string(128)->notNull());
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230820_162023_fix_cache_id_type cannot be reverted.\n";
        return false;
    }
}
