<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use yii\web\UrlManager;

/**
 * m161125_150752_clear_urlmanager_cache migration.
 */
class m161125_150752_clear_urlmanager_cache extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        Craft::$app->getCache()->delete(UrlManager::class);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161125_150752_clear_urlmanager_cache cannot be reverted.\n";

        return false;
    }
}
