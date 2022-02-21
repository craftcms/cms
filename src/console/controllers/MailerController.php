<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\MailerHelper;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

/**
 * Allows for testing mailer settings via the CLI.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class MailerController extends Controller
{
    /**
     * @var string|null Email address that should receive the test message.
     * @since 3.5.0
     */
    public ?string $to = null;

    /**
     * @inheritdoc
     * @since 3.5.0
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if ($actionID === 'test') {
            $options[] = 'to';
        }
        return $options;
    }

    /**
     * Tests sending an email with the current mailer settings.
     *
     * @return int
     * @throws InvalidConfigException
     */
    public function actionTest(): int
    {
        if (isset($this->to)) {
            $to = $this->to;
        } else {
            $testToEmailAddress = Craft::$app->getConfig()->getGeneral()->getTestToEmailAddress();
            $to = $this->prompt('Which email address should the test email be sent to?', [
                'default' => ArrayHelper::firstKey($testToEmailAddress),
            ]);
        }

        $mailer = Craft::$app->getMailer();
        $settingsReport = preg_replace('/^- \*\*([\w\ \-]+):\*\*/m', '    $1:', MailerHelper::settingsReport($mailer));

        $this->stdout("Sending a test email to $to with the following settings:");
        $this->stdout(PHP_EOL . PHP_EOL . $settingsReport . PHP_EOL, Console::FG_YELLOW);

        $message = $mailer
            ->composeFromKey('test_email', [
                'user' => new User(['username' => $to, 'email' => $to]),
                'settings' => $settingsReport,
            ])
            ->setTo($to);

        if (!$message->send()) {
            $this->stderr('There was an error testing your email settings. Please check the logs.' . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Email sent successfully! Check your inbox.' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
