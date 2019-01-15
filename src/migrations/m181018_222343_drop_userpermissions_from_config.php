<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m181018_222343_drop_userpermissions_from_config migration.
 */
class m181018_222343_drop_userpermissions_from_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.4', '>=')) {
            return;
        }

        $projectConfig->remove('users.permissions');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181018_222343_drop_userpermissions_from_config cannot be reverted.\n";
        return false;
    }
}
