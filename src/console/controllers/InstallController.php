<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\elements\User;
use craft\helpers\Install as InstallHelper;
use craft\migrations\Install;
use craft\models\Site;
use Seld\CliPrompt\CliPrompt;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Craft CMS CLI installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InstallController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The default email address for the first user to create during install
     */
    public $email;

    /**
     * @var string|null The default username for the first user to create during install
     */
    public $username;

    /**
     * @var string|null The default password for the first user to create during install
     */
    public $password;

    /**
     * @var string|null The default site name for the first site to create during install
     */
    public $siteName;

    /**
     * @var string|null The default site url for the first site to create during install
     */
    public $siteUrl;

    /**
     * @var string|null The default langcode for the first site to create during install
     */
    public $language;

    public $defaultAction = 'craft';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'craft') {
            $options[] = 'email';
            $options[] = 'username';
            $options[] = 'password';
            $options[] = 'siteName';
            $options[] = 'siteUrl';
            $options[] = 'language';
        }

        return $options;
    }

    /**
     * Runs the install migration
     *
     * @return int
     */
    public function actionCraft(): int
    {
        if (Craft::$app->getIsInstalled()) {
            $this->stdout('Craft is already installed!' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        // Validate the arguments
        $errors = [];
        if ($this->username && !$this->validateUsername($this->username, $error)) {
            $errors[] = $error;
        }
        if ($this->email && !$this->validateEmail($this->email, $error)) {
            $errors[] = $error;
        }
        if ($this->password && !$this->validatePassword($this->password, $error)) {
            $errors[] = $error;
        }
        if ($this->siteName && !$this->validateSiteName($this->siteName, $error)) {
            $errors[] = $error;
        }
        if ($this->siteUrl && !$this->validateSiteUrl($this->siteUrl, $error)) {
            $errors[] = $error;
        }
        if ($this->language && !$this->validateLanguage($this->language, $error)) {
            $errors[] = $error;
        }

        if (!empty($errors)) {
            $this->stderr('Invalid arguments:' . PHP_EOL . '    - ' . implode(PHP_EOL . '    - ', $errors) . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $username = $this->username ?: $this->prompt('Username:', ['validator' => [$this, 'validateUsername'], 'default' => 'admin']);
        $email = $this->email ?: $this->prompt('Email:', ['required' => true, 'validator' => [$this, 'validateEmail']]);
        $password = $this->password ?: $this->_passwordPrompt();
        $siteName = $this->siteName ?: $this->prompt('Site name:', ['required' => true, 'default' => InstallHelper::defaultSiteName(), 'validator' => [$this, 'validateSiteName']]);
        $siteUrl = $this->siteUrl ?: $this->prompt('Site URL:', ['required' => true, 'default' => InstallHelper::defaultSiteUrl(), 'validator' => [$this, 'validateSiteUrl']]);
        $language = $this->language ?: $this->prompt('Site language:', ['default' => InstallHelper::defaultSiteLanguage(), 'validator' => [$this, 'validateLanguage']]);

        // Try to save the site URL to a DEFAULT_SITE_URL environment variable
        // if it's not already set to an alias or environment variable
        if ($siteUrl[0] !== '@' && $siteUrl[0] !== '$') {
            try {
                Craft::$app->getConfig()->setDotEnvVar('DEFAULT_SITE_URL', $siteUrl);
                $siteUrl = '$DEFAULT_SITE_URL';
            } catch (Exception $e) {
                // that's fine, we'll just store the entered URL
            }
        }

        $site = new Site([
            'name' => $siteName,
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => $siteUrl,
            'language' => $language,
        ]);

        $migration = new Install([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'site' => $site,
        ]);

        // Run the install migration
        $this->stdout('*** installing Craft' . PHP_EOL, Console::FG_YELLOW);
        $start = microtime(true);
        $migrator = Craft::$app->getMigrator();
        $result = $migrator->migrateUp($migration);

        if ($result === false) {
            $this->stderr('*** failed to install Craft' . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** installed Craft successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        // Mark all existing migrations as applied
        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        return ExitCode::OK;
    }

    /**
     * Installs a plugin
     *
     * @param string $handle
     * @return int
     */
    public function actionPlugin(string $handle): int
    {
        $this->stdout("*** installing {$handle}" . PHP_EOL, Console::FG_YELLOW);
        $start = microtime(true);

        try {
            Craft::$app->plugins->installPlugin($handle);
        } catch (\Throwable $e) {
            $this->stderr("*** failed to install {$handle}: {$e->getMessage()}" . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** installed {$handle} successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    public function validateUsername(string $value, string &$error = null): bool
    {
        return $this->_validateUserAttribute('username', $value, $error);
    }

    /**
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    public function validateEmail(string $value, string &$error = null): bool
    {
        return $this->_validateUserAttribute('email', $value, $error);
    }

    /**
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    public function validatePassword(string $value, string &$error = null): bool
    {
        return $this->_validateUserAttribute('newPassword', $value, $error);
    }

    /**
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    public function validateSiteName(string $value, string &$error = null): bool
    {
        return $this->_validateSiteAttribute('name', $value, $error);
    }

    /**
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    public function validateSiteUrl(string $value, string &$error = null): bool
    {
        return $this->_validateSiteAttribute('baseUrl', $value, $error);
    }

    /**
     * @param string $value
     * @param string|null $error
     * @return bool
     */
    public function validateLanguage(string $value, string &$error = null): bool
    {
        return $this->_validateSiteAttribute('language', $value, $error);
    }

    // Private Methods
    // =========================================================================

    private function _validateUserAttribute(string $attribute, $value, &$error): bool
    {
        $user = new User([$attribute => $value]);
        if (!$user->validate([$attribute])) {
            $error = $user->getFirstError($attribute);
            return false;
        }
        $error = null;
        return true;
    }

    private function _validateSiteAttribute(string $attribute, $value, &$error): bool
    {
        $site = new Site([$attribute => $value]);
        if (!$site->validate([$attribute])) {
            $error = $site->getFirstError($attribute);
            return false;
        }
        $error = null;
        return true;
    }

    private function _passwordPrompt(): string
    {
        // todo: would be nice to replace CliPrompt with a native Yii silent prompt
        // (https://github.com/yiisoft/yii2/issues/10551)
        top:
        $this->stdout('Password: ');
        if (($password = CliPrompt::hiddenPrompt(true)) === '') {
            $this->stdout('Invalid input.' . PHP_EOL);
            goto top;
        }
        if (!$this->validatePassword($password, $error)) {
            Console::output($error);
            goto top;
        }
        $this->stdout('Confirm: ');
        if (!($matched = ($password === CliPrompt::hiddenPrompt(true)))) {
            $this->stdout('Passwords didn\'t match, try again.' . PHP_EOL, Console::FG_RED);
            goto top;
        }
        return $password;
    }
}
