<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * m181130_143040_fix_schema_version migration.
 */
class m181130_143040_fix_schema_version extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Previous 3.1 Beta releases weren't setting system.schemaVersion
        // on install, so set it to the previous one just in case there are
        // future migrations run alongside this one that are checking it
        $projectConfig = Craft::$app->getProjectConfig();
        if ($projectConfig->get(ProjectConfig::CONFIG_SCHEMA_VERSION_KEY) === null) {
            $projectConfig->set(ProjectConfig::CONFIG_SCHEMA_VERSION_KEY, '3.1.8');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181130_143040_fix_schema_version cannot be reverted.\n";
        return false;
    }
}
