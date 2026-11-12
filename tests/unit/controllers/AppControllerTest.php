<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\controllers;

use Craft;
use craft\controllers\AppController;
use craft\elements\User;
use craft\helpers\Session;
use craft\models\EntryType;
use craft\test\TestCase;
use crafttests\fixtures\EntryTypeFixture;
use yii\web\BadRequestHttpException;

/**
 * Unit tests for AppController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.4
 */
class AppControllerTest extends TestCase
{
    private ?string $originalRequestMethod = null;

    public function _fixtures(): array
    {
        return [
            'entry-types' => [
                'class' => EntryTypeFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider processApiResponseHeadersDataProvider
     */
    public function testProcessApiResponseHeaders(bool $admin, bool $expectAllowTrials): void
    {
        Craft::$app->getUser()->setIdentity(new User([
            'id' => 1,
            'admin' => $admin,
        ]));

        $this->processApiResponseHeaders([
            'x-craft-allow-trials' => '1',
            'x-craft-license-domain' => 'example.com',
        ]);

        $cache = Craft::$app->getCache();
        $trialsCacheKey = $this->allowTrialsCacheKey();
        if ($expectAllowTrials) {
            self::assertSame(1, $cache->get($trialsCacheKey));
        } else {
            self::assertFalse($cache->get($trialsCacheKey));
        }

        // Non-privileged headers should always be processed
        self::assertSame('example.com', $cache->get('licensedDomain'));
    }

    public static function processApiResponseHeadersDataProvider(): array
    {
        return [
            'admin' => [true, true],
            'non-admin' => [false, false],
        ];
    }

    public function testRenderComponentsOverrides(): void
    {
        $iconPath = dirname(__DIR__, 2) . '/_data/assets/files/craft-logo.svg';
        $html = $this->renderComponents(EntryType::class, 1000, [
            'name' => 'Overridden Name',
            'icon' => $iconPath,
        ])['components'][EntryType::class][1000][0];

        self::assertStringContainsString('Overridden Name', $html);
        self::assertStringNotContainsString('<svg', $html);
    }

    public function testRenderComponentsInvalidType(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->renderComponents(Session::class, 'foo');
    }

    private function renderComponents(string $type, mixed $id, array $overrides = []): array
    {
        Craft::$app->getUser()->setIdentity(new User(['id' => 1, 'admin' => false]));
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(true);
        $request->getHeaders()->set('Accept', 'application/json');
        $request->setBodyParams([
            'components' => [
                ['type' => $type, 'id' => $id, 'instances' => [['showThumb' => true, 'overrides' => $overrides]]],
            ],
        ]);

        $controller = new AppController('app', Craft::$app);
        return $controller->actionRenderComponents()->data;
    }

    private function processApiResponseHeaders(array $headers): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = Craft::$app->getRequest();
        $request->setIsCpRequest(true);
        $request->setBodyParams(['headers' => $headers]);

        $controller = new AppController('app', Craft::$app);
        $controller->actionProcessApiResponseHeaders();
    }

    private function allowTrialsCacheKey(): string
    {
        return sprintf('editionTestableDomain@%s', Craft::$app->getRequest()->getHostName());
    }

    private function clearCache(): void
    {
        $cache = Craft::$app->getCache();
        $cache->delete($this->allowTrialsCacheKey());
        $cache->delete('licensedDomain');
    }

    protected function _before(): void
    {
        parent::_before();
        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->clearCache();
    }

    protected function _after(): void
    {
        Craft::$app->getUser()->setIdentity(null);
        Craft::$app->getRequest()->setIsCpRequest(null);
        $this->clearCache();

        if ($this->originalRequestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::_after();
    }
}
