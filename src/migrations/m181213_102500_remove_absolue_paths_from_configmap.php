<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\services\Sections;
use yii\helpers\Json;

/**
 * m181213_102500_remove_absolue_paths_from_configmap migration.
 */
class m181213_102500_remove_absolue_paths_from_configmap extends Migration
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

        $info->configMap = $configMap;

        Craft::$app->saveInfo($info);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181213_102500_remove_absolue_paths_from_configmap cannot be reverted.\n";
        return false;
    }
}
