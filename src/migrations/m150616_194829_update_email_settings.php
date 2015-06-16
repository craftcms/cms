<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\MigrationHelper;
use yii\db\Schema;

/**
 * m150616_194829_update_email_settings migration.
 */
class m150616_194829_update_email_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $systemSettingsService = Craft::$app->getSystemSettings();
        $settings = $systemSettingsService->getSettings('email');

        if (!empty($settings)) {
            $settingsMap = [
                'emailAddress' => 'fromEmail',
                'senderName' => 'fromName',
                'smtpAuth' => 'useAuthentication',
                'smtpSecureTransportType' => 'encryptionMethod',
            ];

            foreach ($settingsMap as $oldSetting => $newSetting) {
                if (isset($settings[$oldSetting])) {
                    $settings[$newSetting] = $settings[$oldSetting];
                    unset($settings[$oldSetting]);
                }
            }

            if (isset($settings['encryptionMethod']) && $settings['encryptionMethod'] == 'none') {
                unset($settings['encryptionMethod']);
            }

            // These are no longer needed
            unset($settings['smtpKeepAlive'], $settings['testEmailAddress']);

            $systemSettingsService->saveSettings('email', $settings);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150616_194829_update_email_settings cannot be reverted.\n";
        return false;
    }
}
