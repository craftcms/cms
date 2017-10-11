<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\console\controllers;

use Craft;
use craft\elements\User;
use craft\errors\InvalidPluginException;
use craft\migrations\Install;
use craft\models\Site;
use Seld\CliPrompt\CliPrompt;
use yii\base\Exception;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Craft CMS CLI installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID === 'index') {
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
     */
    public function actionIndex()
    {
        if (Craft::$app->getIsInstalled()) {
            $this->stdout('Craft is already installed!'.PHP_EOL, Console::FG_YELLOW);
            return;
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
            $this->stderr('Invalid arguments:'.PHP_EOL.'    - '.implode(PHP_EOL.'    - ', $errors).PHP_EOL);
            return;
        }

        $username = $this->username ?: $this->prompt('Username:', ['validator' => [$this, 'validateUsername'], 'default' => 'admin']);
        $email = $this->email ?: $this->prompt('Email:', ['required' => true, 'validator' => [$this, 'validateEmail']]);
        $password = $this->password ?: $this->_passwordPrompt();
        $siteName = $this->siteName ?: $this->prompt('Site name:', ['required' => true, 'validator' => [$this, 'validateSiteName']]);
        $siteUrl = $this->siteUrl ?: $this->prompt('Site URL:', ['required' => true, 'validator' => [$this, 'validateSiteUrl']]);
        $language = $this->language ?: $this->prompt('Site language:', ['validator' => [$this, 'validateLanguage'], 'default' => 'en-US']);

        $this->stdout('Installing Craft...'.PHP_EOL, Console::FG_YELLOW);

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
        $migrator = Craft::$app->getMigrator();

        if ($migrator->migrateUp($migration) !== false) {
            $this->stdout('Success!'.PHP_EOL, Console::FG_GREEN);

            // Mark all existing migrations as applied
            foreach ($migrator->getNewMigrations() as $name) {
                $migrator->addMigrationHistory($name);
            }
        } else {
            $this->stderr('There was a problem installing Craft.'.PHP_EOL, Console::FG_RED);
        }
    }

    /**
     * Installs a plugin
     *
     * @param string $handle
     */
    public function actionPlugin(string $handle)
    {
        try {
            Craft::$app->plugins->installPlugin($handle);
            $this->stdout($handle.' sucessfully installed!'.PHP_EOL);
        } catch (InvalidPluginException $e) {
            $this->stderr('Could not find a plugin with the handle: '.$handle.PHP_EOL);
        } catch (Exception $e) {
            $this->stderr("There was a problem installing {$handle}: ".$e->getMessage().PHP_EOL);
        }
    }

    public function validateUsername(string $value, string &$error = null): bool
    {
        return $this->_validateUserAttribute('username', $value, $error);
    }

    public function validateEmail(string $value, string &$error = null): bool
    {
        return $this->_validateUserAttribute('email', $value, $error);
    }

    public function validatePassword(string $value, string &$error = null): bool
    {
        return $this->_validateUserAttribute('newPassword', $value, $error);
    }

    public function validateSiteName(string $value, string &$error = null): bool
    {
        return $this->_validateSiteAttribute('name', $value, $error);
    }

    public function validateSiteUrl(string $value, string &$error = null): bool
    {
        return $this->_validateSiteAttribute('baseUrl', $value, $error);
    }

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
        if (($password = CliPrompt::hiddenPrompt()) === '') {
            $this->stdout('Invalid input.'.PHP_EOL);
            goto top;
        }
        $this->stdout('Confirm: ');
        if (!($matched = ($password === CliPrompt::hiddenPrompt()))) {
            $this->stdout('Passwords didn\'t match, try again.'.PHP_EOL, Console::FG_RED);
            goto top;
        }
        return $password;
    }
}
