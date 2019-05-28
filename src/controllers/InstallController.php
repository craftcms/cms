<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\config\DbConfig;
use craft\db\Connection;
use craft\elements\User;
use craft\errors\DbConnectException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Install as InstallHelper;
use craft\helpers\StringHelper;
use craft\migrations\Install;
use craft\models\Site;
use craft\web\assets\installer\InstallerAsset;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\Response;
use yii\web\BadRequestHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The InstallController class is a controller that directs all installation related tasks such as creating the database
 * schema and default content for a Craft installation.
 * Note that all actions in the controller are open to do not require an authenticated Craft session in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InstallController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws BadRequestHttpException if Craft is already installed
     */
    public function init()
    {
        // Return a 404 if Craft is already installed
        if (!YII_DEBUG && Craft::$app->getIsInstalled()) {
            throw new BadRequestHttpException('Craft is already installed');
        }

        parent::init();
    }

    /**
     * Index action.
     *
     * @return Response|string The requirements check response if the server doesn’t meet Craft’s requirements, or the rendering result
     * @throws \Throwable if it's an Ajax request and the server doesn’t meet Craft’s requirements
     * @throws DbConnectException if a .env file can't be found and the current DB credentials are invalid
     */
    public function actionIndex()
    {
        if (($response = Craft::$app->runAction('templates/requirements-check')) !== null) {
            return $response;
        }

        // Can we establish a DB connection?
        try {
            Craft::$app->getDb()->open();
            $showDbScreen = false;
        } catch (DbConnectException $e) {
            // Can we control the settings?
            if ($this->_canControlDbConfig()) {
                $showDbScreen = true;
            } else {
                throw $e;
            }
        }

        $this->getView()->registerAssetBundle(InstallerAsset::class);

        // Grab the license text
        $licensePath = dirname(Craft::$app->getBasePath()) . '/LICENSE.md';
        $license = file_get_contents($licensePath);

        // Guess the site name based on the server name
        $defaultSystemName = InstallHelper::defaultSiteName();
        $defaultSiteUrl = InstallHelper::defaultSiteUrl();
        $defaultSiteLanguage = InstallHelper::defaultSiteLanguage();

        $iconsPath = Craft::getAlias('@app/icons');
        $dbIcon = $showDbScreen ? file_get_contents($iconsPath . DIRECTORY_SEPARATOR . 'database.svg') : null;
        $userIcon = file_get_contents($iconsPath . DIRECTORY_SEPARATOR . 'user.svg');
        $worldIcon = file_get_contents($iconsPath . DIRECTORY_SEPARATOR . 'world.svg');

        return $this->renderTemplate('_special/install', compact(
            'showDbScreen',
            'license',
            'defaultSystemName',
            'defaultSiteUrl',
            'defaultSiteLanguage',
            'dbIcon',
            'userIcon',
            'worldIcon'
        ));
    }

    /**
     * Validates the DB connection settings.
     *
     * @return Response
     */
    public function actionValidateDb()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $dbConfig = new DbConfig();
        $this->_populateDbConfig($dbConfig);

        $errors = [];

        // Catch any low hanging fruit first
        if (!$dbConfig->port) {
            // Only possible if it was not numeric
            $errors['port'][] = Craft::t('yii', '{attribute} must be an integer.', [
                'attribute' => Craft::t('app', 'Port')
            ]);
        }
        if (!$dbConfig->database) {
            $errors['database'][] = Craft::t('yii', '{attribute} cannot be blank.', [
                'attribute' => Craft::t('app', 'Database Name')
            ]);
        }
        if (strlen(StringHelper::ensureRight($dbConfig->tablePrefix, '_')) > 6) {
            $errors['tablePrefix'][] = Craft::t('app', 'Prefix must be 5 or less characters long.');
        }

        if (empty($errors)) {
            // Test the connection
            $dbConfig->updateDsn();
            /** @var Connection $db */
            $db = Craft::createObject(App::dbConfig($dbConfig));

            try {
                $db->open();
            } catch (DbConnectException $e) {
                /** @var \PDOException $pdoException */
                $pdoException = $e->getPrevious()->getPrevious();
                switch ($pdoException->getCode()) {
                    case 1045:
                        $attr = 'user';
                        break;
                    case 1049:
                        $attr = 'database';
                        break;
                    case 2002:
                        $attr = 'server';
                        break;
                    default:
                        $attr = '*';
                }
                $errors[$attr][] = 'PDO exception: ' . $pdoException->getMessage();
            }
        }

        $validates = empty($errors);

        return $this->asJson(compact('validates', 'errors'));
    }

    /**
     * Validates the user account credentials.
     *
     * @return Response
     */
    public function actionValidateAccount(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $user = new User(['scenario' => User::SCENARIO_REGISTRATION]);
        $request = Craft::$app->getRequest();
        $user->email = $request->getBodyParam('email');
        $user->username = $request->getBodyParam('username', $user->email);
        $user->newPassword = $request->getBodyParam('password');

        $validates = $user->validate();
        $errors = $user->getErrors();

        if (isset($errors['newPassword'])) {
            $errors['password'] = ArrayHelper::remove($errors, 'newPassword');
        }

        return $this->asJson(compact('validates', 'errors'));
    }

    /**
     * Validates the site settings.
     *
     * @return Response
     */
    public function actionValidateSite(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $site = new Site();
        $site->name = $request->getBodyParam('name');
        $site->baseUrl = $request->getBodyParam('baseUrl');
        $site->language = $request->getBodyParam('language');

        $validates = $site->validate(['name', 'baseUrl', 'language']);
        $errors = $site->getErrors();

        return $this->asJson(compact('validates', 'errors'));
    }

    /**
     * Install action.
     *
     * @return Response
     */
    public function actionInstall(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $configService = Craft::$app->getConfig();

        // Should we set the new DB config values?
        if ($request->getBodyParam('db-driver') !== null) {
            // Set and save the new DB config values
            $dbConfig = Craft::$app->getConfig()->getDb();
            $this->_populateDbConfig($dbConfig, 'db-');

            $configService->setDotEnvVar('DB_DRIVER', $dbConfig->driver);
            $configService->setDotEnvVar('DB_SERVER', $dbConfig->server);
            $configService->setDotEnvVar('DB_USER', $dbConfig->user);
            $configService->setDotEnvVar('DB_PASSWORD', $dbConfig->password);
            $configService->setDotEnvVar('DB_DATABASE', $dbConfig->database);
            $configService->setDotEnvVar('DB_SCHEMA', $dbConfig->schema);
            $configService->setDotEnvVar('DB_TABLE_PREFIX', $dbConfig->tablePrefix);
            $configService->setDotEnvVar('DB_PORT', $dbConfig->port);

            // Update the db component based on new values
            /** @var Connection $db */
            $db = Craft::createObject(App::dbConfig($dbConfig));
            Craft::$app->set('db', $db);
        }

        // Run the install migration
        $migrator = Craft::$app->getMigrator();

        $email = $request->getBodyParam('account-email');
        $username = $request->getBodyParam('account-username', $email);
        $siteUrl = $request->getBodyParam('site-baseUrl');

        // Don't save @web even if they chose it
        if ($siteUrl === '@web') {
            $siteUrl = Craft::getAlias($siteUrl);
        }

        // Try to save the site URL to a DEFAULT_SITE_URL environment variable
        // if it's not already set to an alias or environment variable
        if ($siteUrl[0] !== '@' && $siteUrl[0] !== '$') {
            try {
                $configService->setDotEnvVar('DEFAULT_SITE_URL', $siteUrl);
                $siteUrl = '$DEFAULT_SITE_URL';
            } catch (Exception $e) {
                // that's fine, we'll just store the entered URL
            }
        }

        $site = new Site([
            'name' => $request->getBodyParam('site-name'),
            'handle' => 'default',
            'hasUrls' => true,
            'baseUrl' => $siteUrl,
            'language' => $request->getBodyParam('site-language'),
        ]);

        $migration = new Install([
            'username' => $username,
            'password' => $request->getBodyParam('account-password'),
            'email' => $email,
            'site' => $site,
        ]);

        if ($migrator->migrateUp($migration) !== false) {
            $success = true;

            // Mark all existing migrations as applied
            foreach ($migrator->getNewMigrations() as $name) {
                $migrator->addMigrationHistory($name);
            }
        } else {
            $success = false;
        }

        return $this->asJson(['success' => $success]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether it looks like we have control over the DB config settings.
     *
     * @return bool
     */
    private function _canControlDbConfig(): bool
    {
        // If the .env file doesn't exist, we definitely can't do anyting about it
        if (!file_exists(Craft::$app->getConfig()->getDotEnvPath())) {
            return false;
        }

        // Map the DB settings we definitely care about to their environment variable names
        $vars = [
            'driver' => 'DB_DRIVER',
            'server' => 'DB_SERVER',
            'user' => 'DB_USER',
            'password' => 'DB_PASSWORD',
            'database' => 'DB_DATABASE',
            //'DB_SCHEMA',
            //'DB_TABLE_PREFIX',
            //'DB_PORT',
        ];

        // Save the current environment variable values, and set temporary ones
        $realValues = [];
        $tempValues = [];

        foreach ($vars as $setting => $var) {
            $realValues[$setting] = getenv($var);
            $tempValues[$setting] = StringHelper::randomString();
            putenv("{$var}={$tempValues[$setting]}");
        }

        // Grab the new DB config. Maybe it will contain our temporary values
        $config = Craft::$app->getConfig()->getConfigFromFile('db');

        // Put the old values back
        foreach ($vars as $setting => $var) {
            if ($realValues[$setting] === false) {
                putenv($var);
            } else {
                putenv("{$var}={$realValues[$setting]}");
            }
        }

        // Now see if our temp values made it in
        foreach ($vars as $setting => $var) {
            if (!isset($config[$setting]) || $config[$setting] !== $tempValues[$setting]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Populates a DbConfig object with post data.
     *
     * @param DbConfig $dbConfig The DbConfig object
     * @param string $prefix The post param prefix to use
     */
    private function _populateDbConfig(DbConfig $dbConfig, string $prefix = '')
    {
        $request = Craft::$app->getRequest();

        $dbConfig->dsn = null;
        $dbConfig->url = null;
        $dbConfig->driver = $request->getRequiredBodyParam($prefix . 'driver');
        $dbConfig->server = $request->getBodyParam($prefix . 'server') ?: 'localhost';
        $dbConfig->port = $request->getBodyParam($prefix . 'port');
        $dbConfig->user = $request->getBodyParam($prefix . 'user') ?: 'root';
        $dbConfig->password = $request->getBodyParam($prefix . 'password');
        $dbConfig->database = $request->getBodyParam($prefix . 'database');
        $dbConfig->schema = $request->getBodyParam($prefix . 'schema') ?: 'public';
        $dbConfig->tablePrefix = $request->getBodyParam($prefix . 'tablePrefix');

        $dbConfig->init();
    }
}
