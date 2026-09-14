<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\helpers\Api;
use craft\helpers\App;
use craft\services\Plugins;
use craft\test\TestCase;
use craft\test\TestSetup;
use Yii;

class ApiHelperTest extends TestCase
{
    private bool $_craftWasWarmed = false;

    protected function setUp(): void
    {
        if (Craft::$app === null) {
            $app = TestSetup::warmCraft();
            Craft::$app = $app;
            Yii::$app = $app;
            $this->_craftWasWarmed = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->_craftWasWarmed) {
            TestSetup::tearDownCraft();
        }
    }

    public function testPluginLicenseWriteIsSkippedWhenProjectConfigIsReadOnly(): void
    {
        $cache = Craft::$app->getCache();
        $cache->delete(App::CACHE_KEY_LICENSE_INFO);

        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $plugins = Craft::$app->getPlugins();

        try {
            $projectConfig->readOnly = true;
            $pluginsMock = $this->createMock(Plugins::class);
            $pluginsMock->expects(self::never())
                ->method('setPluginLicenseKey');
            Craft::$app->set('plugins', $pluginsMock);

            Api::processResponseHeaders([
                'X-Craft-Plugin-Licenses' => 'example-plugin:license-key',
                'X-Craft-License-Info' => 'example-plugin:123;standard;valid',
            ]);

            $licenseInfo = $cache->get(App::CACHE_KEY_LICENSE_INFO);
            self::assertSame('123', $licenseInfo['example-plugin']['id']);
            self::assertSame('standard', $licenseInfo['example-plugin']['edition']);
            self::assertSame('valid', $licenseInfo['example-plugin']['status']);
        } finally {
            Craft::$app->set('plugins', $plugins);
            $projectConfig->readOnly = $readOnly;
        }
    }

    public function testPluginLicenseWriteOccursWhenProjectConfigIsWritable(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $readOnly = $projectConfig->readOnly;
        $plugins = Craft::$app->getPlugins();

        try {
            $projectConfig->readOnly = false;
            $pluginsMock = $this->createMock(Plugins::class);
            $pluginsMock->expects(self::once())
                ->method('setPluginLicenseKey')
                ->with('example-plugin', 'license-key')
                ->willReturn(true);
            Craft::$app->set('plugins', $pluginsMock);

            Api::processResponseHeaders([
                'X-Craft-Plugin-Licenses' => 'example-plugin:license-key',
            ]);
        } finally {
            Craft::$app->set('plugins', $plugins);
            $projectConfig->readOnly = $readOnly;
        }
    }

    /**
     * @dataProvider projectConfigReadOnlyDataProvider
     */
    public function testResponseWithoutPluginLicensesDoesNotWritePluginLicense(bool $readOnly): void
    {
        $cache = Craft::$app->getCache();
        $cache->delete('licensedDomain');

        $projectConfig = Craft::$app->getProjectConfig();
        $originalReadOnly = $projectConfig->readOnly;
        $plugins = Craft::$app->getPlugins();

        try {
            $projectConfig->readOnly = $readOnly;
            $pluginsMock = $this->createMock(Plugins::class);
            $pluginsMock->expects(self::never())
                ->method('setPluginLicenseKey');
            Craft::$app->set('plugins', $pluginsMock);

            Api::processResponseHeaders([
                'X-Craft-License-Domain' => 'example.test',
            ]);

            self::assertSame('example.test', $cache->get('licensedDomain'));
        } finally {
            Craft::$app->set('plugins', $plugins);
            $projectConfig->readOnly = $originalReadOnly;
        }
    }

    public static function projectConfigReadOnlyDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }
}
