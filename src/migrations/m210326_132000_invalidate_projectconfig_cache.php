<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * m210326_132000_invalidate_projectconfig_cache migration.
 */
class m210326_132000_invalidate_projectconfig_cache extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        Craft::$app->getCache()->delete(ProjectConfig::STORED_CACHE_KEY);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210326_132000_invalidate_projectconfig_cache cannot be reverted.\n";
        return false;
    }
}
