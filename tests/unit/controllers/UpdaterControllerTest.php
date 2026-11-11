<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\BaseUpdaterController;
use craft\controllers\UpdaterController;
use craft\elements\User;
use craft\helpers\Json;
use craft\services\Composer;
use craft\test\TestCase;

/**
 * Tests updater behavior when system Composer is unavailable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class UpdaterControllerTest extends TestCase
{
    private ?string $originalRequestMethod;
    private string|false $originalPath;
    private Composer $originalComposer;

    public function testMissingComposerDoesNotStartUpdate(): void
    {
        Craft::$app->getUser()->setIdentity(new User(['id' => 1, 'admin' => true]));
        $this->prepareRequest([
            'install' => ['craft' => '^5.13.0'],
            'packageNames' => ['craft' => 'craftcms/cms'],
        ]);

        try {
            $this->controller()->runAction('index');
            self::assertFalse(Craft::$app->getIsInMaintenanceMode());
            $js = implode("\n", array_merge(...array_values(Craft::$app->getView()->js)));
            self::assertSame(1, preg_match('/\.setState\((.+)\);/', $js, $matches));
            $state = Json::decode($matches[1]);
            self::assertStringContainsString('Composer 2.2.0 or later', $state['error']);
            self::assertArrayNotHasKey('nextAction', $state);
            self::assertSame('recheck-composer', $state['options'][0]['nextAction']);
        } finally {
            Craft::$app->disableMaintenanceMode();
        }
    }

    /** @dataProvider missingComposerActionDataProvider */
    public function testMissingComposerActionsReturnExplanation(string $actionId): void
    {
        // Deliberately no package data. The unavailable response must precede any attempted changes.
        $this->prepareRequest(['data' => Craft::$app->getSecurity()->hashData(Json::encode([]))]);
        $response = $this->controller()->runAction($actionId);

        self::assertStringContainsString('Composer 2.2.0 or later', $response->data['error']);
        self::assertArrayNotHasKey('nextAction', $response->data);
        self::assertSame($actionId, $response->data['options'][0]['nextAction']);
    }

    public static function missingComposerActionDataProvider(): array
    {
        return [
            ['composer-install'],
            ['composer-remove'],
            ['revert'],
        ];
    }

    public function testMigrationPrecheckRemainsAvailableWithoutComposer(): void
    {
        $data = Craft::$app->getSecurity()->hashData(Json::encode([
            'migrate' => ['craft'],
            'postPrecheckState' => ['nextAction' => UpdaterController::ACTION_BACKUP],
        ]));
        $this->prepareRequest(['data' => $data]);

        $response = $this->controller()->runAction(BaseUpdaterController::ACTION_PRECHECK);

        self::assertSame(UpdaterController::ACTION_BACKUP, $response->data['nextAction']);
    }

    private function controller(): UpdaterController
    {
        $controller = new UpdaterController('updater', Craft::$app);
        $controller->enableCsrfValidation = false;
        return $controller;
    }

    private function prepareRequest(array $bodyParams): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(true);
        $request->setBodyParams($bodyParams);
    }

    protected function _before(): void
    {
        parent::_before();
        $this->originalPath = getenv('PATH');
        $this->originalComposer = Craft::$app->getComposer();
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        putenv('PATH=' . sys_get_temp_dir() . '/craft-no-composer-' . uniqid());
        Craft::$app->set('composer', new Composer());
    }

    protected function _after(): void
    {
        putenv($this->originalPath === false ? 'PATH' : "PATH=$this->originalPath");
        Craft::$app->set('composer', $this->originalComposer);
        Craft::$app->getUser()->setIdentity(null);
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(null);
        $request->setBodyParams([]);

        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
