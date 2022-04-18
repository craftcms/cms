<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * m220120_131800_default_auth_settings migration.
 */
class m220120_131800_default_auth_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            $userSettings = $projectConfig->get(ProjectConfig::PATH_USERS);
            $userSettings['allowWebAuthn'] = true;
            $userSettings['require2fa'] = [];
            $projectConfig->set(ProjectConfig::PATH_USERS, $userSettings);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220120_131800_default_auth_settings cannot be reverted.\n";
        return false;
    }
}
