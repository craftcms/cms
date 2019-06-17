<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Component;
use craft\console\Controller;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\MailerHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Gmail;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\Smtp;
use craft\models\MailSettings;
use yii\base\InvalidArgumentException;
use yii\console\ExitCode;
use craft\elements\User as UserElement;

/**
 * The TestsController provides various support resources for testing both Craft's own services and your implementation of
 * Craft within your project.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TestsController extends Controller
{
    // Public functions
    // =========================================================================

    /**
     * Allows for the testing of email settings within Craft using one of the following scenarios:
     *
     * 1. Testing the default settings used in Craft::$app->getMailer()->send();
     * 2. Test sending according to email settings used in a custom config defined through app.php
     * 3. Define your own custom Transport adapter and test using one off settings.
     *
     * @return int
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionEmailSettings() : int
    {
        $recieverEmail = $this->prompt(PHP_EOL.'Which email address must we send this test email to?');

        $mailParams = [
            'user' => new UserElement([
                'email' => $recieverEmail,
                'username' => $recieverEmail
            ])
        ];

        $settingsModel = App::mailSettings();

        // Default settings?
        if ($this->confirm(PHP_EOL.'Do you want to test using the current email settings?')) {
            $adapter = MailerHelper::createTransportAdapter(
                $settingsModel->transportType,
                $settingsModel->transportSettings
            );

            $mailParams['settings'] = $this->_renderMailSettingsString(
                $settingsModel,
                $adapter
            );

            return $this->_testEmailSending($mailParams, $recieverEmail);
        }

        // Environment settings
        if ($this->confirm(PHP_EOL.'Do you want to use email settings from a specific environment?')) {
            $env = $this->prompt(PHP_EOL.'Which environment do you want to use?');

            // Get the env - then the appropriate config - then set the env back.
            $oldEnv = Craft::$app->getConfig()->env;
            Craft::$app->getConfig()->env = $env;
            $configSettings = Craft::$app->getConfig()->getConfigFromFile('app');
            Craft::$app->getConfig()->env = $oldEnv;

            // Does it even exist?
            if (!isset($configSettings['components']['mailer'])) {
                $this->stderr(PHP_EOL."No mailer configuration was found for the env: $env");
                return ExitCode::OK;
            }

            /* @var Mailer $mailer */
            $mailer = Craft::createObject($configSettings['components']['mailer']);

            Craft::$app->set('mailer', $mailer);

            // TODO: Is there a way to extract the MailSettings and TransportAdapter settings from Craft::$app->getMailer()
            $mailParams['settings'] = '';

            return $this->_testEmailSending($mailParams, $recieverEmail);
        }

        // Otherwise we let the user decide....
        $transportAdapters = [
            $settingsModel->transportType::displayName() => $settingsModel->transportType,
            'Smtp' => Smtp::class,
            'Gmail' => Gmail::class,
            'Sendmail'=> Sendmail::class,
            'Other' => 'Other'
        ];
        $transportAdapters = array_unique($transportAdapters);
        $userInput = $this->select(PHP_EOL.'Which transport type do you want to use?', $transportAdapters);

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
                $selectedOption = $this->prompt(PHP_EOL.'Which custom transport type do you want to use?');
                break;
            default:
                $this->stderr(PHP_EOL.'You have entered an invalid transport type.');
                return ExitCode::OK;
        }

        // Be kind...
        if (!$selectedOption) {
            $selectedOption = $this->prompt(PHP_EOL.'You have not entered a custom transport type - please enter one now.');
        }

        // Create the mailer
        try {
            /* @var BaseTransportAdapter $transport */
            $transport = Component::createComponent([
                'type' => $selectedOption
            ], BaseTransportAdapter::class);
        } catch (\Throwable $exception) {
            $message = $exception->getMessage();
            $this->stderr(PHP_EOL."The following problem occured when creating the mailer: $message", Console::FG_RED);
            return ExitCode::OK;
        }

        // What settings do they want to use?
        foreach ($transport->settingsAttributes() as $property) {
            // Try and find a default.
            $default = null;
            if (isset($settingsModel->transportSettings[$property])) {
                $default = $settingsModel->transportSettings[$property];
            }

            $transport->$property = $this->prompt(PHP_EOL."What must $property be set to?", ['default' => $default]);
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
        return $this->_testEmailSending($mailParams, $recieverEmail);
    }

    /**
     * Sets up a test suite for the current project.
     *
     * @param string|null $dst The folder that the test suite should be generated in.
     * Defaults to the current working directory.
     * @return int
     */
    public function actionSetup(string $dst = null): int
    {
        if ($dst === null) {
            $dst = getcwd();
        }

        $src = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'internal' . DIRECTORY_SEPARATOR . 'example-test-suite';

        // Figure out the plan and check for conflicts
        $plan = [];
        $conflicts = [];

        $handle = opendir($src);
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $src");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;
            $humanTo = $to . (is_dir($from) ? DIRECTORY_SEPARATOR : '');
            $plan[] = $humanTo;
            if (file_exists($to)) {
                $conflicts[] = $humanTo;
            }
        }
        closedir($handle);

        // Warn about conflicts
        if (!empty($conflicts)) {
            $this->stdout('The following files/folders will be overwritten:' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);
            foreach ($conflicts as $file) {
                $this->stdout("- {$file}" . PHP_EOL, Console::FG_YELLOW);
            }
            $this->stdout(PHP_EOL);
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->stdout('Aborting.' . PHP_EOL);
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $this->stdout(PHP_EOL);
        }

        // Confirm
        $this->stdout('The following files/folders will be created:' . PHP_EOL . PHP_EOL);
        foreach ($plan as $file) {
            $this->stdout("- {$file}" . PHP_EOL);
        }
        $this->stdout(PHP_EOL);
        if (!$this->confirm('Continue?', true)) {
            $this->stdout('Aborting.' . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(PHP_EOL . 'Generating the test suite ... ');
        try {
            FileHelper::copyDirectory($src, $dst);
        } catch (\Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stdout('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout('done.' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Dont use this method - it wont actually execute anything.
     * It is just used internally to test Craft-based console controller testing.
     *
     * @return int
     * @internal
     */
    public function actionTest(): int
    {
        $this->stdout('22');
        $this->stderr('123321123');
        $val = $this->select('Select', ['2', '22']);

        if ($val !== '2') {
            throw new InvalidArgumentException('FAIL');
        }

        $confirm = $this->confirm('asd', true);
        if ($confirm !== true) {
            throw new InvalidArgumentException('FAIL');
        }

        $prompts = $this->prompt('A prompt', ['2', '22']);
        if ($prompts !== 'hi') {
            throw new InvalidArgumentException('FAIL');
        }

        $this->outputCommand('An output command');

        return ExitCode::OK;
    }

    // Protected functions
    // =========================================================================

    /**
     * @param MailSettings|null $settings
     * @param null $adapter
     * @return string
     */
    protected function _renderMailSettingsString(MailSettings $settings = null, $adapter = null) : string
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
     * @param $reciever
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    protected function _testEmailSending(array $mailParams, $reciever) : int
    {
        $message = Craft::$app->getMailer()
            ->composeFromKey('test_email', $mailParams)
            ->setTo($reciever);

        if ($message->send()) {
            $this->stdout('Email sent successfully! Check your inbox.'.PHP_EOL.PHP_EOL);
        } else {
            $this->stderr('There was an error testing your email settings.'.PHP_EOL.PHP_EOL);
        }

        return ExitCode::OK;
    }
}
