<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;

/**
 * m181213_102500_config_map_aliases migration.
 */
class m181213_102500_config_map_aliases extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $info = Craft::$app->getInfo();
        $configMap = Json::decode($info->configMap) ?? [];

        foreach ($configMap as &$filePath) {
            $filePath = Craft::alias($filePath);
        }

        $info->configMap = Json::encode($configMap);

        Craft::$app->saveInfo($info);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181213_102500_config_map_aliases cannot be reverted.\n";
        return false;
    }
}
