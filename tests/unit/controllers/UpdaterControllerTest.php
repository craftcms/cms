<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\BaseUpdaterController;
use craft\controllers\pluginstore\RemoveController;
use craft\controllers\UpdaterController;
use craft\elements\User;
use craft\helpers\Json;
use craft\test\TestCase;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Unit tests for the updater controllers’ handling of signed updater data.
 *
 * Composer actions are only run through `beforeAction()`, so a regression can’t end up running Composer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.1
 */
class UpdaterControllerTest extends TestCase
{
    private ?string $originalRequestMethod = null;
    private bool $originalAllowUpdates;
    private bool $originalAllowAdminChanges;

    /**
     * Data signed with the global security key, as the `|hash` filter does, is only trusted for Composer
     * actions when the user can perform updates. Other steps stay anonymous so self-updates can finish.
     *
     * @dataProvider globalKeyDataProvider
     */
    public function testGlobalKeyData(string $actionId, bool $admin, bool $expectAllowed): void
    {
        if ($admin) {
            $this->loginAdmin();
        }

        $data = Craft::$app->getSecurity()->hashData(Json::encode(['requirements' => ['foo/bar' => '1.0.0']]));

        if (!$expectAllowed) {
            $this->expectException(ForbiddenHttpException::class);
        }

        self::assertTrue($this->beforeAction(UpdaterController::class, 'updater', $actionId, $data));
    }

    public static function globalKeyDataProvider(): array
    {
        return [
            'composer-install, guest' => [BaseUpdaterController::ACTION_COMPOSER_INSTALL, false, false],
            'revert, guest' => [UpdaterController::ACTION_REVERT, false, false],
            'composer-install, admin' => [BaseUpdaterController::ACTION_COMPOSER_INSTALL, true, true],
            'revert, admin' => [UpdaterController::ACTION_REVERT, true, true],
            'server-check, guest' => [UpdaterController::ACTION_SERVER_CHECK, false, true],
            'migrate, guest' => [UpdaterController::ACTION_MIGRATE, false, true],
            'finish, guest' => [BaseUpdaterController::ACTION_FINISH, false, true],
        ];
    }

    /**
     * @dataProvider updatesDisabledDataProvider
     */
    public function testUpdatesDisabled(string $actionId, bool $allowUpdates, bool $allowAdminChanges): void
    {
        $this->loginAdmin();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->allowUpdates = $allowUpdates;
        $generalConfig->allowAdminChanges = $allowAdminChanges;

        $data = Craft::$app->getSecurity()->hashData(Json::encode([]));

        $this->expectException(ForbiddenHttpException::class);
        $this->expectExceptionMessage('Updates are disabled.');
        $this->beforeAction(UpdaterController::class, 'updater', $actionId, $data);
    }

    public static function updatesDisabledDataProvider(): array
    {
        return [
            'composer-install, allowUpdates off' => [BaseUpdaterController::ACTION_COMPOSER_INSTALL, false, true],
            'composer-install, allowAdminChanges off' => [BaseUpdaterController::ACTION_COMPOSER_INSTALL, true, false],
            'revert, allowUpdates off' => [UpdaterController::ACTION_REVERT, false, true],
            'revert, allowAdminChanges off' => [UpdaterController::ACTION_REVERT, true, false],
        ];
    }

    public function testUpdatesDisabledStillFinishesUp(): void
    {
        Craft::$app->getConfig()->getGeneral()->allowUpdates = false;
        $data = Craft::$app->getSecurity()->hashData(Json::encode([]));

        self::assertTrue($this->beforeAction(UpdaterController::class, 'updater', UpdaterController::ACTION_MIGRATE, $data));
    }

    public function testUpdaterDataRoundTrips(): void
    {
        $data = $this->indexData();

        self::assertTrue($this->beforeAction(UpdaterController::class, 'updater', BaseUpdaterController::ACTION_FINISH, $data));
    }

    public function testUpdaterDataCantBeReplayedToAnotherController(): void
    {
        $this->loginAdmin();
        $data = $this->indexData();

        $this->expectException(BadRequestHttpException::class);
        $this->beforeAction(RemoveController::class, 'pluginstore/remove', BaseUpdaterController::ACTION_FINISH, $data);
    }

    public function testUnsignedDataIsRejected(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->beforeAction(UpdaterController::class, 'updater', BaseUpdaterController::ACTION_FINISH, Json::encode([]));
    }

    public function testPrecheckWithoutPendingUpdate(): void
    {
        // updater/index hands out this data anonymously when there's nothing to update
        $data = $this->indexData();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Request contained invalid updater data.');
        $this->runUpdaterAction(BaseUpdaterController::ACTION_PRECHECK, $data);
    }

    public function testPrecheckWithPendingUpdate(): void
    {
        $data = Craft::$app->getSecurity()->hashData(Json::encode([
            'migrate' => ['craft'],
            'postPrecheckState' => ['nextAction' => UpdaterController::ACTION_BACKUP],
        ]));

        $response = $this->runUpdaterAction(BaseUpdaterController::ACTION_PRECHECK, $data);

        self::assertSame(UpdaterController::ACTION_BACKUP, $response->data['nextAction']);
    }

    private function beforeAction(string $class, string $controllerId, string $actionId, string $data): bool
    {
        $this->prepareRequest($data);
        $controller = $this->controller($class, $controllerId);
        return $controller->beforeAction($controller->createAction($actionId));
    }

    private function runUpdaterAction(string $actionId, string $data): mixed
    {
        $this->prepareRequest($data);
        return $this->controller(UpdaterController::class, 'updater')->runAction($actionId);
    }

    /**
     * Returns the updater data that updater/index embeds in the page.
     */
    private function indexData(): string
    {
        $this->prepareRequest(null);
        $this->controller(UpdaterController::class, 'updater')->runAction('index');
        $js = implode("\n", array_merge(...array_values(Craft::$app->getView()->js)));

        self::assertSame(1, preg_match('/"data":("(?:[^"\\\\]|\\\\.)*")/', $js, $match), 'updater/index didn’t include updater data.');
        return Json::decode($match[1]);
    }

    private function controller(string $class, string $id): BaseUpdaterController
    {
        /** @var BaseUpdaterController $controller */
        $controller = new $class($id, Craft::$app);
        $controller->enableCsrfValidation = false;
        return $controller;
    }

    private function prepareRequest(?string $data): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(true);
        $request->setBodyParams($data !== null ? ['data' => $data] : []);
    }

    private function loginAdmin(): void
    {
        Craft::$app->getUser()->setIdentity(new User([
            'id' => 1,
            'admin' => true,
        ]));
    }

    protected function _before(): void
    {
        parent::_before();
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->originalAllowUpdates = $generalConfig->allowUpdates;
        $this->originalAllowAdminChanges = $generalConfig->allowAdminChanges;
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity(null);
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(null);
        $request->setBodyParams([]);
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->allowUpdates = $this->originalAllowUpdates;
        $generalConfig->allowAdminChanges = $this->originalAllowAdminChanges;

        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
