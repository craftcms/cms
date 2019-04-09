<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m190208_140000_reset_project_config_mapping migration.
 */
class m190208_140000_reset_project_config_mapping extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Invalidate the existing config mapping.
        $info = Craft::$app->getInfo();
        $info->configMap = null;
        Craft::$app->saveInfo($info);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190208_140000_reset_project_config_mapping cannot be reverted.\n";
        return false;
    }
}
