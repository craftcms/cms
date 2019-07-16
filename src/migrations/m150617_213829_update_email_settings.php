<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;

/**
 * m150617_213829_update_email_settings migration.
 */
class m150617_213829_update_email_settings extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $oldSettings = (new Query())
            ->select(['settings'])
            ->where(['category' => 'email'])
            ->from(['{{%systemsettings}}'])
            ->scalar();

        if ($oldSettings) {
            $oldSettings = Json::decodeIfJson($oldSettings);

            if (isset($oldSettings['emailAddress']) && isset($oldSettings['senderName']) && isset($oldSettings['protocol'])) {
                // Start assembling the new settings
                $settings = [
                    'fromEmail' => $oldSettings['emailAddress'],
                    'fromName' => $oldSettings['senderName'],
                    'template' => $oldSettings['template'] ?? null,
                ];

                // Protocol-specific stuff
                switch ($oldSettings['protocol']) {
                    case 'smtp':
                        $settings['transportType'] = Smtp::class;
                        $settings['transportSettings'] = [
                            'host' => $oldSettings['host'] ?? null,
                            'port' => $oldSettings['port'] ?? null,
                            'useAuthentication' => $oldSettings['smtpAuth'] ?? false,
                            'username' => $oldSettings['username'] ?? null,
                            'password' => $oldSettings['password'] ?? null,
                            'encryptionMethod' => isset($oldSettings['smtpSecureTransportType']) && $oldSettings['smtpSecureTransportType'] !== 'none' ? $oldSettings['smtpSecureTransportType'] : null,
                            'timeout' => $oldSettings['timeout'] ?? 10,
                        ];
                        break;
                    case 'gmail':
                        $settings['transportType'] = Gmail::class;
                        $settings['transportSettings'] = [
                            'username' => $oldSettings['username'] ?? null,
                            'password' => $oldSettings['password'] ?? null,
                            'timeout' => $oldSettings['timeout'] ?? 10,
                        ];
                        break;
                    default:
                        $settings['transportType'] = Sendmail::class;
                }

                // Save the new settings
                $this->update('{{%systemsettings}}', ['settings' => Json::encode($settings)], ['category' => 'email'], [], false);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150617_213829_update_email_settings cannot be reverted.\n";

        return false;
    }
}
