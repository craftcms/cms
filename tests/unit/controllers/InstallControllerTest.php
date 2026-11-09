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
use craft\helpers\App;
use craft\test\TestCase;
use craft\web\ServiceUnavailableHttpException;
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
        $controller = $this->controller();
        self::assertTrue($controller->beforeAction($controller->createAction('install')));
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

    protected function _after(): void
    {
        if (isset($this->originalDb)) {
            Craft::$app->set('db', $this->originalDb);
        }
        Craft::$app->setIsInstalled(null);
        parent::_after();
    }
}
