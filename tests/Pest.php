<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestCase;
use CraftCms\Cms\Tests\TestClasses\TestPlugin;
use CraftCms\Cms\Tests\UnitTestCase;

uses(TestCase::class)->in('Feature');
uses(UnitTestCase::class)->in('Unit');
uses(TestCase::class)->beforeEach(function () {
    $this->withVite();
})->in('Browser');

beforeEach(function () {
    app()->forgetInstance(GeneralConfig::class);
});

/**
 * Sync @web alias and Yii2 asset manager base URL to match the Pest browser
 * test server URL. Must be called in the test closure (after the browser
 * server has bootstrapped) before the first visit() call.
 */
function configureBrowserUrls(): void
{
    $serverUrl = rtrim((string) config('app.url'), '/');
    \CraftCms\Aliases\Aliases::set('@web', $serverUrl);

    if (\Craft::$app) {
        \Craft::$app->getAssetManager()->baseUrl = $serverUrl.'/cpresources';
    }
}

function loadTestPlugin(): void
{
    $plugins = app(Plugins::class);

    $reflectionClass = new ReflectionClass($plugins);
    $reflectionClass->getProperty('plugins')->setValue($plugins, [
        'test-plugin' => TestPlugin::create([
            'handle' => 'test-plugin',
            'name' => 'Test Plugin',
            'version' => '1.0.1',
        ]),
    ]);
    $reflectionClass->getProperty('composerPluginInfo')->setValue($plugins, [
        'test-plugin' => [
            'name' => 'Test Plugin',
            'packageName' => 'craftcms/test-plugin',
            'version' => '1.0.1',
            'class' => TestPlugin::class,
            'basePath' => __DIR__.'/TestClasses',
        ],
    ]);
    $reflectionClass->getProperty('storedPluginInfo')->setValue($plugins, [
        'test-plugin' => [
            'id' => 1,
            'name' => 'Test Plugin',
            'handle' => 'test-plugin',
            'version' => '1.0.1',
            'schemaVersion' => '1.0.0',
            'installDate' => $now = now(),
            'dateCreated' => $now,
            'dateUpdated' => $now,
            'uid' => Str::uuid(),
            'edition' => 'standard',
            'licensedEdition' => 'pro',
            'licenseKeyStatus' => LicenseKeyStatus::Trial->value,
            'settings' => [],
            'licenseKey' => null,
            'enabled' => false,
        ],
    ]);
    $reflectionClass->getProperty('pluginsLoaded')->setValue($plugins, true);
}

function entryQuery(array $config = []): EntryQuery
{
    return new EntryQuery($config);
}

function assetQuery(array $config = []): AssetQuery
{
    return new AssetQuery($config);
}

function userQuery(array $config = []): UserQuery
{
    return new UserQuery($config);
}
