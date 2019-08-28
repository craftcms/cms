<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\User as UserElement;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\Component;
use craft\helpers\Console;
use craft\helpers\MailerHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use craft\models\MailSettings;
use Throwable;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

/**
 * Allows for testing mailer settings via the CLI.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class MailerController extends Controller
{
    // Public functions
    // =========================================================================

    /**
     * Allows for the testing of email settings within Craft using one of the following scenarios:
     *
     * 1. Testing the default settings used in Craft::$app->getMailer()->send();
     * 2. Test sending according to email settings used in a custom config defined through app.php
     * 3. Choose your own custom Transport adapter and test using once off settings.
     *
     * @return int
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function actionTest(): int
    {
        $receiverEmail = $this->prompt(PHP_EOL . 'Which email address should we send this test email to?');

        $mailParams = [
            'user' => new UserElement([
                'email' => $receiverEmail,
                'username' => $receiverEmail
            ])
        ];

        $settingsModel = App::mailSettings();

        // Default settings?
        if ($this->confirm(PHP_EOL . 'Do you want to test using the current email settings?')) {
            $adapter = MailerHelper::createTransportAdapter(
                $settingsModel->transportType,
                $settingsModel->transportSettings
            );

            $mailParams['settings'] = $this->_renderMailSettingsString(
                $settingsModel,
                $adapter
            );

            return $this->_testEmailSending($mailParams, $receiverEmail);
        }

        // Environment settings
        if ($this->confirm(PHP_EOL . 'Do you want to use email settings from a specific environment?')) {
            $env = $this->prompt(PHP_EOL . 'Which environment do you want to use?');

            // Get the env - then the appropriate config - then set the env back.
            $oldEnv = Craft::$app->getConfig()->env;
            Craft::$app->getConfig()->env = $env;
            $configSettings = Craft::$app->getConfig()->getConfigFromFile('app');
            Craft::$app->getConfig()->env = $oldEnv;

            // Does it even exist?
            if (!isset($configSettings['components']['mailer'])) {
                $this->stderr(PHP_EOL . "No mailer configuration was found for the env: {$env}");
                return ExitCode::OK;
            }

            /* @var Mailer $mailer */
            $mailer = Craft::createObject($configSettings['components']['mailer']);

            Craft::$app->set('mailer', $mailer);

            $mailParams['settings'] = '';

            return $this->_testEmailSending($mailParams, $receiverEmail);
        }

        // Otherwise we let the user decide....
        $transportAdapters = array_unique([
            $settingsModel->transportType::displayName() => $settingsModel->transportType,
            'Smtp' => Smtp::class,
            'Gmail' => Gmail::class,
            'Sendmail' => Sendmail::class,
            'Other' => 'Other'
        ]);
        $userInput = $this->select(PHP_EOL . 'Which transport type do you want to use?', $transportAdapters);

        // Attempt to resolve the user input into a class
        $selectedOption = null;
        switch ($userInput) {
            case 'Smtp':
                $selectedOption = Smtp::class;
                break;
            case 'Gmail':
                $selectedOption = Gmail::class;
                break;
            case 'Sendmail':
                $selectedOption = Sendmail::class;
                break;
            case 'Other':
                $selectedOption = $this->prompt(PHP_EOL . 'Which custom transport type do you want to use?');
                break;
            default:
                $this->stderr(PHP_EOL . 'You have entered an invalid transport type.');
                return ExitCode::OK;
        }

        // Be kind...
        if (!$selectedOption) {
            $selectedOption = $this->prompt(PHP_EOL . 'You have not entered a custom transport type - please enter one now.');
        }

        // Create the mailer
        try {
            /* @var BaseTransportAdapter $transport */
            $transport = Component::createComponent(['type' => $selectedOption], BaseTransportAdapter::class);
        } catch (Throwable $exception) {
            $message = $exception->getMessage();
            $this->stderr(PHP_EOL . "The following problem occurred when creating the mailer: $message", Console::FG_RED);
            return ExitCode::OK;
        }

        // What settings do they want to use?
        foreach ($transport->settingsAttributes() as $property) {
            // Try and find a default.
            $default = $settingsModel->transportSettings[$property] ?? null;
            $transport->$property = $this->prompt(PHP_EOL . "What must {$property} be set to?", ['default' => $default]);
        }

        // Save the new stuff to the settings
        $settingsModel->transportType = $transport::displayName();
        $settingsModel->transportSettings = $transport->getSettings();

        // Too easy?
        if (!$transport->validate()) {
            $this->stderr('Your email settings are invalid.');
            return ExitCode::OK;
        }

        // Setup the new transport and settings for sending the email.
        Craft::$app->getMailer()->transport = $transport->defineTransport();
        $mailParams['settings'] = $this->_renderMailSettingsString($settingsModel, $transport);

        // FOR... SPARTAAA!
        return $this->_testEmailSending($mailParams, $receiverEmail);
    }

    // Protected functions
    // =========================================================================

    /**
     * Copied from `craft\controllers\SystemSettingsController::actionTestEmailSettings()`
     *
     * @param MailSettings|null $settings
     * @param null $adapter
     * @return string
     */
    protected function _renderMailSettingsString(MailSettings $settings = null, $adapter = null): string
    {
        // Compose the settings list as HTML
        $settingsList = '';

        if ($settings) {
            foreach (['fromEmail', 'fromName', 'template'] as $name) {
                if (!empty($settings->$name)) {
                    $settingsList .= '- **' . $settings->getAttributeLabel($name) . ':** ' . $settings->$name . "\n";
                }
            }
        }


        if ($adapter) {
            $settingsList .= '- **' . 'Transport Type' . ':** ' . $adapter::displayName() . "\n";

            $security = Craft::$app->getSecurity();

            foreach ($adapter->settingsAttributes() as $name) {
                if (!empty($adapter->$name)) {
                    $label = $adapter->getAttributeLabel($name);
                    $value = $security->redactIfSensitive($name, $adapter->$name);
                    $settingsList .= "- **{$label}:** {$value}\n";
                }
            }
        }

        return $settingsList;
    }

    /**
     * @param array $mailParams
     * @param $receiver
     * @return int
     * @throws InvalidConfigException
     */
    protected function _testEmailSending(array $mailParams, $receiver): int
    {
        $message = Craft::$app->getMailer()
            ->composeFromKey('test_email', $mailParams)
            ->setTo($receiver);

        if ($message->send()) {
            $this->stdout('Email sent successfully! Check your inbox.' . PHP_EOL . PHP_EOL);
        } else {
            $this->stderr('There was an error testing your email settings. Please check the Craft log files.' . PHP_EOL . PHP_EOL);
        }

        return ExitCode::OK;
    }
}
