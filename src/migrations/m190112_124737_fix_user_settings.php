<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190112_124737_fix_user_settings migration.
 */
class m190112_124737_fix_user_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.15', '>=')) {
            return;
        }

        $wrong = $projectConfig->get('user') ?? [];
        $right = $projectConfig->get('users') ?? [];
        $projectConfig->set('users', array_merge($wrong, $right));
        $projectConfig->remove('user');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190112_124737_fix_user_settings cannot be reverted.\n";
        return false;
    }
}
