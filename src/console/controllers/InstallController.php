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
use craft\errors\MigrationException;
use craft\helpers\Console;
use craft\helpers\Install as InstallHelper;
use craft\migrations\Install;
use craft\models\Site;
use yii\base\Exception;
use yii\console\ExitCode;

/**
 * Craft CMS CLI installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class InstallController extends Controller
{
    /**
     * @var string|null The default email address for the first user to create during install.
     */
    public ?string $email = null;

    /**
     * @var string|null The default username for the first user to create during install.
     */
    public ?string $username = null;

    /**
     * @var string|null The default password for the first user to create during install.
     */
    public ?string $password = null;

    /**
     * @var string|null The default site name for the first site to create during install.
     */
    public ?string $siteName = null;

    /**
     * @var string|null The default site URL for the first site to create during install.
     */
    public ?string $siteUrl = null;

    /**
     * @var string|null The default language for the first site to create during install.
     */
    public ?string $language = null;

    /** @inheritdoc */
    public $defaultAction = 'craft';

    /**
     * @inheritdoc
     */
    public function options($actionID): array
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
     * Checks whether Craft is already installed.
     *
     * @return int
     * @since 3.5.0
     */
    public function actionCheck(): int
    {
        if (!Craft::$app->getIsInstalled(true)) {
            $this->stdout('Craft is not installed yet.' . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Craft is installed.' . PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Runs the install migration.
     *
     * @return int
     */
    public function actionCraft(): int
    {
        if (Craft::$app->getIsInstalled(true)) {
            $this->stdout('Craft is already installed!' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $user = new User();
        $site = new Site([
            'handle' => 'default',
            'hasUrls' => true,
        ]);

        // Validate the arguments
        $errors = [];
        $currentError = null;

        if ($this->username && !$this->createAttributeValidator($user, 'username')($this->username, $currentError)) {
            $errors[] = $currentError;
        }
        if ($this->email && !$this->createAttributeValidator($user, 'email')($this->email, $currentError)) {
            $errors[] = $currentError;
        }
        if ($this->password && !$this->createAttributeValidator($user, 'newPassword')($this->password, $currentError)) {
            $errors[] = $currentError;
        }
        if ($this->siteName && !$this->createAttributeValidator($site, 'name')($this->siteName, $currentError)) {
            $errors[] = $currentError;
        }
        if ($this->siteUrl && !$this->createAttributeValidator($site, 'baseUrl')($this->siteUrl, $currentError)) {
            $errors[] = $currentError;
        }
        if ($this->language && !$this->createAttributeValidator($site, 'language')($this->language, $currentError)) {
            $errors[] = $currentError;
        }

        if (!empty($errors)) {
            $this->stderr('Invalid arguments:' . PHP_EOL . '    - ' . implode(PHP_EOL . '    - ', $errors) . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $configService = Craft::$app->getConfig();
        $generalConfig = $configService->getGeneral();

        if ($generalConfig->useEmailAsUsername) {
            $username = $email = $this->email ?: $this->prompt('Email:', ['required' => true, 'validator' => $this->createAttributeValidator($user, 'email')]);
        } else {
            $username = $this->username ?: $this->prompt('Username:', ['validator' => $this->createAttributeValidator($user, 'username'), 'default' => 'admin']);
            $email = $this->email ?: $this->prompt('Email:', ['required' => true, 'validator' => $this->createAttributeValidator($user, 'email')]);
        }
        $password = $this->password ?: $this->passwordPrompt(['validator' => $this->createAttributeValidator($user, 'newPassword')]);
        $site->name = $this->siteName ?: $this->prompt('Site name:', ['required' => true, 'default' => InstallHelper::defaultSiteName(), 'validator' => $this->createAttributeValidator($site, 'name')]);
        $site->baseUrl = $this->siteUrl ?: $this->prompt('Site URL:', ['required' => true, 'default' => InstallHelper::defaultSiteUrl(), 'validator' => $this->createAttributeValidator($site, 'baseUrl')]);
        $site->language = $this->language ?: $this->prompt('Site language:', ['default' => InstallHelper::defaultSiteLanguage(), 'validator' => $this->createAttributeValidator($site, 'language')]);

        // Try to save the site URL to a PRIMARY_SITE_URL environment variable
        // if it’s not already set to an alias or environment variable
        if (!in_array($site->getBaseUrl(false)[0], ['@', '$'])) {
            try {
                $configService->setDotEnvVar('PRIMARY_SITE_URL', $site->baseUrl);
                $site->baseUrl = '$PRIMARY_SITE_URL';
            } catch (Exception) {
                // that's fine, we'll just store the entered URL
            }
        }

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

        try {
            $migrator->migrateUp($migration);
        } catch (MigrationException $e) {
            $this->stderr('*** failed to install Craft: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** installed Craft successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        // Mark all existing migrations as applied
        foreach ($migrator->getNewMigrations() as $name) {
            $migrator->addMigrationHistory($name);
        }

        Console::ensureProjectConfigFileExists();

        return ExitCode::OK;
    }

    /**
     * DEPRECATED. Use `plugin/install` instead.
     *
     * @param string $handle
     * @return int
     * @deprecated in 3.5.0. Use `plugin/uninstall` instead.
     */
    public function actionPlugin(string $handle): int
    {
        Console::outputWarning("The install/plugin command is deprecated.\nRunning plugin/install instead...");
        return $this->run('plugin/install', [$handle]);
    }
}
