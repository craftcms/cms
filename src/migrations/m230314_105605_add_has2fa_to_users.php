<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * m230314_105605_add_has2fa_to_users migration.
 */
class m230314_105605_add_has2fa_to_users extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.5.0', '<')) {
            $userSettings = $projectConfig->get(ProjectConfig::PATH_USERS);
            $userSettings['has2fa'] = [];
            $projectConfig->set(ProjectConfig::PATH_USERS, $userSettings);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.5.0', '<')) {
            $userSettings = $projectConfig->get(ProjectConfig::PATH_USERS);
            if (isset($userSettings['has2fa'])) {
                unset($userSettings['has2fa']);
                $projectConfig->set(ProjectConfig::PATH_USERS, $userSettings);
            }
        }

        return true;
    }
}
