<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\InstallController;
use craft\test\TestCase;
use yii\web\NotFoundHttpException;

/**
 * Unit tests for the web-based installer’s guards.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class InstallControllerTest extends TestCase
{
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

    private function controller(): InstallController
    {
        $controller = new InstallController('install', Craft::$app);
        $controller->enableCsrfValidation = false;
        return $controller;
    }

    protected function _after(): void
    {
        Craft::$app->setIsInstalled(null);
        parent::_after();
    }
}
