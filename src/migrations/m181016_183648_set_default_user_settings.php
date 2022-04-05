<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m181016_183648_set_default_user_settings migration.
 */
class m181016_183648_set_default_user_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true) ?? $projectConfig->get('schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.2', '>=')) {
            return;
        }

        $settings = $projectConfig->get('users') ?? [];

        $settings = array_merge([
            'requireEmailVerification' => true,
            'allowPublicRegistration' => false,
            'defaultGroup' => null,
            'photoVolumeUid' => null,
            'photoSubpath' => null,
        ], $settings);

        $projectConfig->set('users', $settings);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181016_183648_set_default_user_settings cannot be reverted.\n";
        return false;
    }
}
