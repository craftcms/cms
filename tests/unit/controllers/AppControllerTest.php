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
use craft\models\Update;
use craft\models\UpdateRelease;
use craft\models\Updates as UpdatesModel;
use craft\services\Composer;
use craft\services\Updates;
use craft\test\TestCase;

/**
 * Unit tests for AppController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.4
 */
class AppControllerTest extends TestCase
{
    private ?string $originalRequestMethod = null;

    /** @dataProvider composerAvailabilityDataProvider */
    public function testUpdateInformationRemainsAvailableWithoutComposer(bool $available): void
    {
        $originalComposer = Craft::$app->getComposer();
        $originalUpdates = Craft::$app->getUpdates();
        $composer = $this->getMockBuilder(Composer::class)->onlyMethods(['getIsAvailable'])->getMock();
        $composer->method('getIsAvailable')->willReturn($available);
        $updates = $this->getMockBuilder(Updates::class)->onlyMethods(['getUpdates'])->getMock();
        $updates->method('getUpdates')->willReturn(new UpdatesModel([
            'cms' => new Update([
                'packageName' => 'craftcms/cms',
                'releases' => [new UpdateRelease(['version' => '5.13.0'])],
            ]),
        ]));
        Craft::$app->set('composer', $composer);
        Craft::$app->set('updates', $updates);
        Craft::$app->getUser()->setIdentity(new User(['id' => 1, 'admin' => true]));
        $request = Craft::$app->getRequest();
        $originalAccept = $request->getHeaders()->get('Accept');
        $request->getHeaders()->set('Accept', 'application/json');
        $request->setBodyParams(['includeDetails' => true]);

        try {
            $response = (new AppController('app', Craft::$app))->actionCheckForUpdates();
            self::assertSame($available, $response->data['allowUpdates']);
            self::assertSame(1, $response->data['total']);
            self::assertSame('5.13.0', $response->data['updates']['cms']['releases'][0]['version']);
        } finally {
            Craft::$app->set('composer', $originalComposer);
            Craft::$app->set('updates', $originalUpdates);
            $request->getHeaders()->set('Accept', $originalAccept);
            $request->setBodyParams([]);
        }
    }

    public static function composerAvailabilityDataProvider(): array
    {
        return [[true], [false]];
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
