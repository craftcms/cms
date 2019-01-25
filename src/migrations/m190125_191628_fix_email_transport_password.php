<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\StringHelper;

/**
 * m190125_191628_fix_email_transport_password migration.
 */
class m190125_191628_fix_email_transport_password extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.23', '>=')) {
            return;
        }

        $configPath = 'email.transportSettings.password';
        if (($password = $projectConfig->get($configPath)) === null) {
            return;
        }

        try {
            $decPassword = StringHelper::decdec($password);
        } catch (\Throwable $e) {
            Craft::error('Could not decode or decrypt the email transport password: ' . $e->getMessage());
            Craft::$app->getErrorHandler()->logException($e);
            return;
        }

        if ($decPassword !== $password) {
            $projectConfig->muteEvents = true;
            $projectConfig->set($configPath, $decPassword);
            $projectConfig->muteEvents = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190125_191628_fix_email_transport_password cannot be reverted.\n";
        return false;
    }
}
