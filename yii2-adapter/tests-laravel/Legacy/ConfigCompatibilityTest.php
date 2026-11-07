<?php

declare(strict_types=1);

use craft\config\GeneralConfig;
use craft\services\Config as LegacyConfig;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Config\GeneralConfigCompatibility;
use CraftCms\Yii2Adapter\Config\MultiEnvironmentConfigCompatibility;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
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
    $config->tempAssetUploadFs = 'asset-uploads';

    expect($config->allowUpdates)->toBeFalse()
        ->and($config->aliases)->toBe(['@uploads' => '/path/to/uploads'])
        ->and($config->tempAssetUploadDisk)->toBe('asset-uploads');
});

it('supports moved deprecated config settings', function(): void {
    $config = GeneralConfig::create();

    expect($config->generateTransformsBeforePageLoad)->toBeNull();

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
        ->and(config('session.domain'))->toBe('.example.test')
        ->and($config->generateTransformsBeforePageLoad)->toBeFalse()
        ->and(Context::getHidden(CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT))->toBeFalse()
        ->and($deprecation?->message)->toBe('The `generateTransformsBeforePageLoad` config setting is deprecated. Configure immediate generation on the Craft asset transformer instead.')
        ->and($config->rememberedUserSessionDuration)->toBe(7200)
        ->and($config->verificationCodeDuration)->toBe(1800);
});

it('accepts the deprecated system live setting without changing maintenance mode', function(): void {
    try {
        $config = GeneralConfig::create()->isSystemLive(false);

        expect($config->isSystemLive)->toBeFalse()
            ->and(app()->isDownForMaintenance())->toBeFalse();

        app()->maintenanceMode()->activate([]);
        $config->isSystemLive(true);

        expect($config->isSystemLive)->toBeTrue()
            ->and(app()->isDownForMaintenance())->toBeTrue()
            ->and(collect(Deprecator::getRequestLogs())
                ->firstWhere('key', 'generalConfig.isSystemLive')?->message)
            ->toContain('Use Laravel maintenance mode instead.');
    } finally {
        app()->maintenanceMode()->deactivate();
    }
});

it('supports the deprecated application live status', function(): void {
    try {
        expect(Craft::$app->getIsLive())->toBeTrue();

        app()->maintenanceMode()->activate([]);

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

it('loads a legacy general config without logging deprecations for settings it leaves alone', function(): void {
    Config::set('craft.general', GeneralConfig::create()->cpTrigger('control'));

    $config = new LegacyConfig()->getConfigSettings(LegacyConfig::CATEGORY_GENERAL);

    expect($config)->toBeInstanceOf(GeneralConfig::class)
        ->and($config->cpTrigger)->toBe('control')
        ->and(collect(Deprecator::getRequestLogs())->pluck('key')->filter(fn(string $key) => str_starts_with($key, 'generalConfig.')))
        ->toBeEmpty();
});

it('logs a deprecation for each deprecated config setting that is set', function(string $method, mixed $value): void {
    GeneralConfig::create()->{$method}($value);

    expect(collect(Deprecator::getRequestLogs())->firstWhere('key', "generalConfig.$method")?->message)
        ->toStartWith("The `$method` config setting is deprecated.");
})->with(function(): iterable {
    $samples = [
        'bool' => true,
        'int' => 13,
        'string' => 'value',
        'array' => [],
        'mixed' => 3600,
    ];

    foreach (new ReflectionClass(GeneralConfig::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getAttributes(Deprecated::class) === [] || $method->name === 'tempAssetUploadFs') {
            continue;
        }

        $type = $method->getParameters()[0]->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        $name = collect($types)->map(fn(ReflectionNamedType $type) => $type->getName())->first(fn(string $name) => isset($samples[$name]));

        yield $method->name => [$method->name, $samples[$name]];
    }
});

it('applies array general configs through the legacy settings', function(): void {
    $config = new GeneralConfigCompatibility()->convert([
        'cpTrigger' => 'control',
        'defaultCookieDomain' => '.example.test',
        'usePathInfo' => true,
    ]);

    expect($config)->toBeInstanceOf(GeneralConfig::class)
        ->and($config->cpTrigger)->toBe('control')
        ->and($config->defaultCookieDomain)->toBe('.example.test')
        ->and(collect(Deprecator::getRequestLogs())->pluck('key'))
        ->toContain('generalConfig.defaultCookieDomain', 'generalConfig.usePathInfo');
});

it('maps the renamed temp asset upload setting', function(): void {
    expect(GeneralConfig::create()->tempAssetUploadFs('uploads')->tempAssetUploadDisk)->toBe('uploads');
});

it('lets application type array overlays replace base settings before they are applied', function(mixed $overlay): void {
    $config = new GeneralConfigCompatibility()->convert(['extraAllowedFileExtensions' => ['foo']], $overlay);

    expect($config->allowedFileExtensions)->not->toContain('foo');
})->with([
    'array' => [['extraAllowedFileExtensions' => []]],
    'closure' => [fn() => fn() => ['extraAllowedFileExtensions' => []]],
    'callable array' => [fn() => [new class() {
        public function web(): array
        {
            return ['extraAllowedFileExtensions' => []];
        }
    }, 'web']],
]);

it('does not restore unset deprecated settings as overrides', function(): void {
    $config = new GeneralConfigCompatibility()->convert([]);
    TrustProxies::at('*');

    eval('$restored = ' . var_export($config, true) . ';');

    expect($restored)->toBeInstanceOf(GeneralConfig::class)
        ->and(Context::hasHidden(CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT))->toBeFalse()
        ->and(proxiedClient())->toBe(['203.0.113.5', 'forwarded.test']);
});

it('accepts null deprecated settings in array general configs', function(): void {
    $config = new GeneralConfigCompatibility()->convert([
        'generateTransformsBeforePageLoad' => null,
        'trustedHosts' => null,
    ]);

    expect($config->generateTransformsBeforePageLoad)->toBeNull()
        ->and($config->trustedHosts)->toBeNull();
});

it('turns CSRF protection back on when a later setting enables it', function(): void {
    PreventRequestForgery::except('webhooks/*');

    $config = GeneralConfig::create()->enableCsrfProtection(false);

    expect(app(PreventRequestForgery::class)->getExcludedPaths())->toContain('*');

    $config->enableCsrfProtection(true);

    expect(app(PreventRequestForgery::class)->getExcludedPaths())->toContain('webhooks/*')->not->toContain('*');
});

it('applies middleware settings again when a cached config is restored', function(GeneralConfig $config): void {
    $state = var_export($config, true);

    PreventRequestForgery::flushState();
    TrustProxies::flushState();

    eval('$restored = ' . $state . ';');

    expect(app(PreventRequestForgery::class)->getExcludedPaths())->toContain('*')
        ->and(proxiedClient())->toBe(['203.0.113.5', 'localhost']);
})->with([
    'array config' => fn() => new GeneralConfigCompatibility()->convert([
        'enableCsrfProtection' => false,
        'trustedHosts' => ['10.0.0.1'],
        'secureHeaders' => ['X-Forwarded-For'],
    ]),
    'config subclass' => fn() => GeneralConfigSubclass::create()
        ->enableCsrfProtection(false)
        ->trustedHosts(['10.0.0.1'])
        ->secureHeaders(['X-Forwarded-For']),
]);

class GeneralConfigSubclass extends GeneralConfig
{
}

/**
 * @return array{string|null, string} The client IP and host of a request forwarded by 10.0.0.1, as Laravel’s trusted
 * proxies middleware sees them
 */
function proxiedClient(): array
{
    $request = Request::create('http://localhost/', server: [
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.5',
        'HTTP_X_FORWARDED_HOST' => 'forwarded.test',
    ]);

    try {
        app(TrustProxies::class)->handle($request, fn() => null);

        return [$request->getClientIp(), $request->getHost()];
    } finally {
        Request::setTrustedProxies([], -1);
    }
}
