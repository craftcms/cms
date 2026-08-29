<?php

declare(strict_types=1);

use craft\config\GeneralConfig;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Config\GeneralConfigCompatibility;
use CraftCms\Yii2Adapter\Config\MultiEnvironmentConfigCompatibility;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;

it('preserves callable config values', function(): void {
    $originalConfigPath = $this->app->configPath();
    $configPath = sys_get_temp_dir() . '/craft-config-' . bin2hex(random_bytes(8));
    $craftConfigPath = "$configPath/craft";
    mkdir($craftConfigPath, recursive: true);
    file_put_contents("$craftConfigPath/general.php", '<?php return [];');
    $this->app->useConfigPath($configPath);

    $config = fn() => GeneralConfig::create();
    Config::set('craft.general', $config);

    try {
        new MultiEnvironmentConfigCompatibility()->register($this->app);

        expect(Config::get('craft.general'))->toBe($config);
    } finally {
        $this->app->useConfigPath($originalConfigPath);
        unlink("$craftConfigPath/general.php");
        rmdir($craftConfigPath);
        rmdir($configPath);
    }
});

it('maps renamed general config settings in the adapter', function(): void {
    $config = GeneralConfig::create();

    $config->allowAutoUpdates = false;
    $config->environmentVariables = ['@uploads' => '/path/to/uploads'];

    expect($config->allowUpdates)->toBeFalse()
        ->and($config->aliases)->toBe(['@uploads' => '/path/to/uploads']);
});

it('supports moved deprecated config settings', function(): void {
    $config = GeneralConfig::create();

    expect($config->generateTransformsBeforePageLoad)->toBeFalse();

    $config
        ->defaultCookieDomain('.example.test')
        ->generateTransformsBeforePageLoad(true)
        ->rememberedUserSessionDuration(7200)
        ->verificationCodeDuration(1800);
    expect(Context::getHidden(CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT))->toBeTrue();

    $config->generateTransformsBeforePageLoad = false;
    $deprecation = collect(Deprecator::getRequestLogs())
        ->firstWhere('key', 'generalConfig.generateTransformsBeforePageLoad');

    expect($config->defaultCookieDomain)->toBe('.example.test')
        ->and($config->generateTransformsBeforePageLoad)->toBeFalse()
        ->and(Context::getHidden(CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT))->toBeFalse()
        ->and($deprecation?->message)->toBe('generateTransformsBeforePageLoad is deprecated. Configure immediate generation on the Craft Asset Transformer instead.')
        ->and($config->rememberedUserSessionDuration)->toBe(7200)
        ->and($config->verificationCodeDuration)->toBe(1800);
});

it('accepts the deprecated system live setting without changing maintenance mode', function(): void {
    $config = GeneralConfig::create()->isSystemLive(false);
    $deprecation = collect(Deprecator::getRequestLogs())
        ->firstWhere('key', 'generalConfig.isSystemLive');

    expect($config->isSystemLive)->toBeFalse()
        ->and(app()->isDownForMaintenance())->toBeFalse()
        ->and($deprecation?->message)->toContain('Use Laravel maintenance mode instead.');
});

it('supports the deprecated application live status', function(): void {
    app()->maintenanceMode()->activate([]);

    try {
        expect(Craft::$app->getIsLive())->toBeFalse()
            ->and(collect(Deprecator::getRequestLogs())
                ->firstWhere('key', 'Craft::$app->getIsLive()')?->message)
            ->toContain('Use ! app()->isDownForMaintenance() instead.');
    } finally {
        app()->maintenanceMode()->deactivate();
    }
});

it('supports adapter resource settings', function(): void {
    $config = GeneralConfig::create()
        ->resourceBasePath('@custom/cpresources')
        ->resourceBaseUrl('https://example.test/cpresources');

    expect($config->resourceBasePath)->toBe('@custom/cpresources')
        ->and($config->resourceBaseUrl)->toBe('https://example.test/cpresources');
});

it('converts callable general config and application type overlays', function(): void {
    $config = new GeneralConfigCompatibility()->convert(
        fn(GeneralConfig $config) => $config->cpTrigger('control'),
        ['cpTrigger' => 'console'],
    );

    expect($config)->toBeInstanceOf(GeneralConfig::class)
        ->and($config->cpTrigger)->toBe('console');
});
