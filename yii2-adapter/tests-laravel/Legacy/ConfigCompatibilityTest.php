<?php

declare(strict_types=1);

use craft\config\GeneralConfig;
use CraftCms\Yii2Adapter\Config\GeneralConfigCompatibility;
use CraftCms\Yii2Adapter\Config\MultiEnvironmentConfigCompatibility;
use Illuminate\Support\Facades\Config;

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
    $config = GeneralConfig::create()
        ->defaultCookieDomain('.example.test')
        ->rememberedUserSessionDuration(7200)
        ->verificationCodeDuration(1800);

    expect($config->defaultCookieDomain)->toBe('.example.test')
        ->and($config->rememberedUserSessionDuration)->toBe(7200)
        ->and($config->verificationCodeDuration)->toBe(1800);
});

it('converts callable general config and application type overlays', function(): void {
    $config = new GeneralConfigCompatibility()->convert(
        fn(GeneralConfig $config) => $config->cpTrigger('control'),
        ['cpTrigger' => 'console'],
    );

    expect($config)->toBeInstanceOf(GeneralConfig::class)
        ->and($config->cpTrigger)->toBe('console');
});
