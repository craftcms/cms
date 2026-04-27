<?php

declare(strict_types=1);

use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Shared\Enums\LicenseKeyStatus;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;

function loadTestPlugin(): void
{
    $plugins = app(Plugins::class);

    $basePathOverride = dirname(__DIR__).'/TestClasses/TestPlugin/src';

    $reflectionClass = new ReflectionClass($plugins);

    $reflectionClass->getProperty('plugins')->setValue($plugins, [
        'test-plugin' => TestPlugin::create([
            'handle' => 'test-plugin',
            'name' => 'Test Plugin',
            'version' => '1.0.1',
            'basePathOverride' => $basePathOverride,
        ]),
    ]);

    $reflectionClass->getProperty('composerPluginInfo')->setValue($plugins, [
        'test-plugin' => [
            'name' => 'Test Plugin',
            'packageName' => 'craftcms/test-plugin',
            'version' => '1.0.1',
            'class' => TestPlugin::class,
            'basePath' => $basePathOverride,
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
