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

/**
 * Unit tests for the Api Helper class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.4
 */
class ApiHelperTest extends TestCase
{
    /**
     * @dataProvider licensedDomainDataProvider
     */
    public function testLicensedDomain(string $domain, bool $expectCached): void
    {
        Api::processResponseHeaders([
            'X-Craft-License-Domain' => $domain,
        ]);

        $cached = Craft::$app->getCache()->get('licensedDomain');
        if ($expectCached) {
            self::assertSame($domain, $cached);
        } else {
            self::assertFalse($cached);
        }
    }

    public static function licensedDomainDataProvider(): array
    {
        return [
            'domain' => ['example.com', true],
            'subdomain' => ['www.example.co.uk', true],
            'localhost' => ['localhost', true],
            'html' => ['poison.test"><img src=x onerror=alert(document.domain)>', false],
            'space' => ['example .com', false],
            'empty' => ['', false],
        ];
    }

    public function testLicenseInfo(): void
    {
        Api::processResponseHeaders([
            'x-craft-license-info' => implode(',', [
                'craft:1;pro;mismatched',
                'plugin-foo-bar:2;standard;valid',
                'plugin-trial:;;trial',
                'plugin-invalid:invalid',
                // invalid entries
                'plugin-bad-id:1"><img src=x>;standard;valid',
                'plugin-bad-edition:3;<img src=x>;valid',
                'plugin-bad-status:4;standard;bogus',
                'plugin-missing-values:5;standard',
                'plugin-<img src=x>:6;standard;valid',
                'notaplugin:7;standard;valid',
                'nocolon',
            ]),
        ]);

        $licenseInfo = Craft::$app->getCache()->get(App::CACHE_KEY_LICENSE_INFO);
        self::assertIsArray($licenseInfo);
        self::assertSame(['craft', 'plugin-foo-bar', 'plugin-trial', 'plugin-invalid'], array_keys($licenseInfo));

        self::assertSame('1', $licenseInfo['craft']['id']);
        self::assertSame('pro', $licenseInfo['craft']['edition']);
        self::assertSame('mismatched', $licenseInfo['craft']['status']);
        self::assertSame('invalid', $licenseInfo['plugin-invalid']['status']);
        self::assertNull($licenseInfo['plugin-invalid']['id']);
    }

    public function testPluginLicenseWriteIsSkippedWhenProjectConfigIsReadOnly(): void
    {
        $cache = Craft::$app->getCache();

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
                'X-Craft-License-Info' => 'plugin-example-plugin:123;standard;valid',
            ]);

            $licenseInfo = $cache->get(App::CACHE_KEY_LICENSE_INFO);
            self::assertSame('123', $licenseInfo['plugin-example-plugin']['id']);
            self::assertSame('standard', $licenseInfo['plugin-example-plugin']['edition']);
            self::assertSame('valid', $licenseInfo['plugin-example-plugin']['status']);
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

    protected function _before(): void
    {
        parent::_before();
        $this->clearCache();
    }

    protected function _after(): void
    {
        $this->clearCache();
        parent::_after();
    }

    private function clearCache(): void
    {
        $cache = Craft::$app->getCache();
        $cache->delete('licensedDomain');
        $cache->delete(App::CACHE_KEY_LICENSE_INFO);
        $cache->delete(App::CACHE_KEY_LICENSE_INFO_HOST);
    }
}
