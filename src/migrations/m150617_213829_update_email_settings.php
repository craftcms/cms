<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;

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
        $systemSettingsService = Craft::$app->getSystemSettings();
        $oldSettings = $systemSettingsService->getSettings('email');

        if (isset($oldSettings['emailAddress']) && isset($oldSettings['senderName']) && isset($oldSettings['protocol'])) {
            // Start assembling the new settings
            $settings = [
                'fromEmail' => $oldSettings['emailAddress'],
                'fromName' => $oldSettings['senderName'],
                'template' => isset($oldSettings['template']) ? $oldSettings['template'] : null,
            ];

            // Start assembling the Mailer config
            $mailerConfig = [
                'class' => 'craft\app\mail\Mailer',
                'from' => [$settings['fromEmail'] => $settings['fromName']],
                'template' => $settings['template'],
            ];

            // Protocol-specific stuff
            switch ($oldSettings['protocol']) {
                case 'sendmail': {
                    $settings['transportType'] = 'craft\app\mail\transportadaptors\Sendmail';
                    $mailerConfig['transport'] = [
                        'class' => 'Swift_SendmailTransport'
                    ];
                    break;
                }
                case 'smtp': {
                    $settings['transportType'] = 'craft\app\mail\transportadaptors\Smtp';
                    $settings['transportSettings'] = [
                        'host' => isset($oldSettings['host']) ? $oldSettings['host'] : null,
                        'port' => isset($oldSettings['port']) ? $oldSettings['port'] : null,
                        'useAuthentication' => isset($oldSettings['smtpAuth']) ? $oldSettings['smtpAuth'] : false,
                        'username' => isset($oldSettings['username']) ? $oldSettings['username'] : null,
                        'password' => isset($oldSettings['password']) ? $oldSettings['password'] : null,
                        'encryptionMethod' => isset($oldSettings['smtpSecureTransportType']) && $oldSettings['smtpSecureTransportType'] != 'none' ? $oldSettings['smtpSecureTransportType'] : null,
                        'timeout' => isset($oldSettings['timeout']) ? $oldSettings['timeout'] : 10,
                    ];
                    $mailerConfig['transport'] = [
                        'class' => 'Swift_SmtpTransport',
                        'host' => $settings['transportSettings']['host'],
                        'port' => $settings['transportSettings']['port'],
                        'timeout' => $settings['transportSettings']['timeout'],
                    ];
                    if ($settings['transportSettings']['useAuthentication']) {
                        $mailerConfig['username'] = $settings['transportSettings']['username'];
                        $mailerConfig['password'] = $settings['transportSettings']['password'];
                    }
                    if ($settings['transportSettings']['encryptionMethod']) {
                        $mailerConfig['encryption'] = $settings['transportSettings']['encryptionMethod'];
                    }
                    break;
                }
                case 'gmail': {
                    $settings['transportType'] = 'craft\app\mail\transportadaptors\Gmail';
                    $settings['transportSettings'] = [
                        'username' => isset($oldSettings['username']) ? $oldSettings['username'] : null,
                        'password' => isset($oldSettings['password']) ? $oldSettings['password'] : null,
                        'timeout' => isset($oldSettings['timeout']) ? $oldSettings['timeout'] : 10,
                    ];
                    $mailerConfig['transport'] = [
                        'class' => 'Swift_SmtpTransport',
                        'host' => 'smtp.gmail.com',
                        'port' => 465,
                        'encryption' => 'ssl',
                        'username' => $settings['transportSettings']['username'],
                        'password' => $settings['transportSettings']['password'],
                        'timeout' => $settings['transportSettings']['timeout'],
                    ];
                    break;
                }
                default: {
                    $settings['transportType'] = 'craft\app\mail\transportadaptors\Php';
                    $mailerConfig['transport'] = [
                        'class' => 'Swift_MailTransport'
                    ];
                }
            }

            // Save the new settings
            $systemSettingsService->saveSettings('email', $settings);
            $systemSettingsService->saveSettings('mailer', $mailerConfig);
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
