<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\App;
use ReflectionClass;

/**
 * m220912_042255_add_email_transports migration.
 */
class m220912_042255_add_email_transports extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $schemaVersion = Craft::$app->getProjectConfig()->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0.10', '>')) {
            return true;
        }

        $mailSettings = App::mailSettings();

        if (!$mailSettings?->transportType) {
            return true;
        }

        $transportReflection = new ReflectionClass($mailSettings->transportType);
        $transportKey = strtolower($transportReflection->getShortName());

        Craft::$app->getProjectConfig()->set("email.transport", $transportKey);
        Craft::$app->getProjectConfig()->set("email.transports.$transportKey", [
            'type' => $mailSettings->transportType,
            'settings' => $mailSettings->transportSettings,
        ]);
        Craft::$app->getProjectConfig()->remove('email.transportType');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220912_042255_add_email_transports cannot be reverted.\n";
        return false;
    }
}
