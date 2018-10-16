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
        $settings = $projectConfig->get('users') ?? [];

        $settings = array_merge([
            'requireEmailVerification' => true,
            'allowPublicRegistration' => false,
            'defaultGroup' => null,
            'photoVolumeUid' => null,
            'photoSubpath' => ''
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
