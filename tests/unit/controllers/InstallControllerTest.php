<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\InstallController;
use craft\db\Connection;
use craft\db\MigrationManager;
use craft\helpers\App;
use craft\services\Config;
use craft\test\TestCase;
use craft\web\ServiceUnavailableHttpException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Unit tests for the web-based installer’s guards.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class InstallControllerTest extends TestCase
{
    private ?Connection $originalDb = null;
    private ?MigrationManager $originalMigrator = null;
    private ?Config $originalConfig = null;
    private ?string $originalDotEnvAlias = null;
    private ?string $originalRequestMethod = null;

    /**
     * The installer must stay closed outside Dev Mode, even when Craft thinks it isn’t installed,
     * such as when it can’t connect to the database.
     */
    public function testInstallerRequiresDevMode(): void
    {
        Craft::$app->setIsInstalled(false);
        $controller = new class('install', Craft::$app) extends InstallController {
            protected function devMode(): bool
            {
                return false;
            }
        };

        $this->expectException(NotFoundHttpException::class);
        $controller->beforeAction($controller->createAction('install'));
    }

    public function testInstallerAllowedInDevMode(): void
    {
        Craft::$app->setIsInstalled(false);
        $controller = $this->controller();
        self::assertTrue($controller->beforeAction($controller->createAction('install')));
    }

    /**
     * The installer must stay closed once Craft is installed, even in Dev Mode.
     */
    public function testInstallerUnavailableWhenInstalled(): void
    {
        Craft::$app->setIsInstalled(true);
        $controller = $this->controller();

        $this->expectException(BadRequestHttpException::class);
        $controller->beforeAction($controller->createAction('install'));
    }

    /**
     * A configured database that can’t be reached doesn’t mean Craft isn’t installed.
     */
    public function testInstallerUnavailableWhenDbIsUnreachable(): void
    {
        $this->useUnreachableDb();
        $controller = $this->controller();

        $this->expectException(ServiceUnavailableHttpException::class);
        $controller->beforeAction($controller->createAction('install'));
    }

    /**
     * Database connection settings posted to the installer must only be saved once Craft is installed with them.
     */
    public function testFailedInstallDoesNotSaveDbSettings(): void
    {
        $dotEnv = "CRAFT_DB_SERVER=original-server\n";
        $dotEnvPath = tempnam(sys_get_temp_dir(), 'CraftInstallTest');
        file_put_contents($dotEnvPath, $dotEnv);

        $this->originalConfig = Craft::$app->getConfig();
        $config = new Config();
        foreach (['appType', 'env', 'configDir', 'appDefaultsDir'] as $property) {
            if (isset($this->originalConfig->$property)) {
                $config->$property = $this->originalConfig->$property;
            }
        }
        $this->originalDotEnvAlias = Craft::getAlias('@dotenv', false) ?: null;
        Craft::setAlias('@dotenv', $dotEnvPath);
        Craft::$app->set('config', $config);

        // Give the installer its own DB connection and migrator to point at the posted server
        $this->originalDb = Craft::$app->getDb();
        $this->originalMigrator = Craft::$app->getMigrator();
        Craft::$app->set('db', Craft::createObject(App::dbConfig($config->getDb())));
        Craft::$app->set('migrator', [
            'class' => MigrationManager::class,
            'track' => MigrationManager::TRACK_CRAFT,
            'migrationNamespace' => 'craft\migrations',
            'migrationPath' => '@app/migrations',
        ]);

        Craft::$app->setIsInstalled(false);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->getHeaders()->set('Accept', 'application/json');
        $request->setBodyParams([
            'db-driver' => $config->getDb()->driver,
            'db-server' => '127.0.0.1',
            'db-port' => '1',
            'db-database' => 'craft',
            'db-user' => 'attacker',
            'db-password' => 'secret',
            'db-tablePrefix' => 'evil',
            'account-email' => 'admin@example.com',
            'account-password' => 'password123',
            'site-name' => 'Test',
            'site-baseUrl' => '$PRIMARY_SITE_URL',
            'site-language' => 'en-US',
        ]);

        try {
            $response = $this->controller()->runAction('install');
            self::assertSame(400, $response->getStatusCode());
            self::assertSame($dotEnv, file_get_contents($dotEnvPath));
            self::assertArrayNotHasKey('CRAFT_DB_SERVER', $_SERVER);
        } finally {
            unlink($dotEnvPath);
        }
    }

    /**
     * Swaps in a DB connection that refuses connections, which is restored after the test.
     */
    private function useUnreachableDb(): void
    {
        $this->originalDb = Craft::$app->getDb();
        $dbConfig = clone Craft::$app->getConfig()->getDb();
        $dbConfig->server = '127.0.0.1';
        $dbConfig->port = 1;
        $dbConfig->dsn = "$dbConfig->driver:host=127.0.0.1;port=1;dbname=$dbConfig->database";
        Craft::$app->set('db', Craft::createObject(App::dbConfig($dbConfig)));
        Craft::$app->setIsInstalled(null);
    }

    private function controller(): InstallController
    {
        $controller = new InstallController('install', Craft::$app);
        $controller->enableCsrfValidation = false;
        return $controller;
    }

    protected function _before(): void
    {
        parent::_before();
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function _after(): void
    {
        if (isset($this->originalDb)) {
            Craft::$app->set('db', $this->originalDb);
        }
        if (isset($this->originalMigrator)) {
            Craft::$app->set('migrator', $this->originalMigrator);
        }
        if (isset($this->originalConfig)) {
            Craft::$app->set('config', $this->originalConfig);
            Craft::setAlias('@dotenv', $this->originalDotEnvAlias);
        }
        Craft::$app->setIsInstalled(null);

        $request = Craft::$app->getRequest();
        $request->setBodyParams([]);
        $request->getHeaders()->remove('Accept');
        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
