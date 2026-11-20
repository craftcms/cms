<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Config\AnchorsConfig;
use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Config\GqlConfig;
use CraftCms\Cms\Config\McpConfig;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\SnapshotPluginSettings;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

afterEach(function () {
    unset($_SERVER['CRAFT_CACHED_ENV_TEST']);
    putenv('CRAFT_CACHED_ENV_TEST');

    foreach (['CRAFT_ANCHORS', 'CRAFT_CP_TRIGGER', 'CRAFT_DEFAULT_COUNTRY_CODE', 'CRAFT_MCP', 'CRAFT_MCP_INSTRUCTIONS', 'CRAFT_ANCHORS_USE_ADDITIONAL_TAG_TO_ANCHOR_TO', 'CRAFT_GQL', 'CRAFT_GQL_MAX_DEPTH', 'CRAFT_GQL_MAX_COMPLEXITY', 'CRAFT_MAX_GRAPHQL_DEPTH', 'CRAFT_MAX_GRAPHQL_COMPLEXITY', 'CRAFT_DEFAULT_IMAGE_QUALITY', 'CRAFT_QUEUE_NAME', 'CRAFT_BACKUP_COMMAND', 'CRAFT_EXTRA_ALLOWED_FILE_EXTENSIONS', 'CRAFT_ASSETS_UPLOAD_CHUNK_SIZE', 'CRAFT_DEFAULT_TEMPLATE_EXTENSIONS', 'CRAFT_SLUG_WORD_SEPARATOR'] as $name) {
        unset($_SERVER[$name]);
        putenv($name);
    }
});

it('normalizes only direct settings before boot without resolving runtime services', function () {
    $input = new SnapshotPluginSettings()->title(' File ');
    $state = (array) $input;
    $general = GeneralConfig::create();
    $closure = fn () => throw new RuntimeException('Not invoked');
    $untouched = [
        'general' => $general,
        'sparse' => ['title' => 'File'],
        'nested' => ['object' => $input],
        'scalar' => 42,
        'null' => null,
        'closure' => $closure,
        'unrelated' => new stdClass,
    ];
    $repository = new Repository(['craft' => [
        ...$untouched,
        'unknown.plugin' => $input,
        'disabled' => new SnapshotPluginSettings,
        'empty' => new class extends PluginSettings {},
    ], 'other' => $input]);
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $app->instance('config', $repository);
    foreach ([Plugins::class, TestPlugin::class, 'db', 'db.connection', 'session', 'session.store', 'view', 'validator'] as $service) {
        $app->bind($service, fn () => throw new RuntimeException("Unexpected resolution: $service"));
        app()->beforeResolving($service, fn () => throw new RuntimeException("Unexpected parent resolution: $service"));
    }

    new ConfigServiceProvider($app)->register();

    $normalized = $repository->get('craft');
    expect($app->isBooted())->toBeFalse()
        ->and($normalized['unknown.plugin'])->toBe($input->validationData())
        ->and($normalized['disabled']['title'])->toBe('Default')
        ->and($normalized['empty'])->toBe([])
        ->and($repository->get('other'))->toBe($input)
        ->and((array) $input)->toBe($state);
    foreach ($untouched as $key => $value) {
        expect($normalized[$key])->toBe($value);
    }

    new ConfigServiceProvider($app)->register();

    expect(Arr::except($repository->get('craft'), 'twig-sandbox'))->toBe(Arr::except($normalized, 'twig-sandbox'))
        ->and((array) $input)->toBe($state);
});

it('uses custom configuration data without changing validation data', function () {
    $input = new class extends PluginSettings
    {
        private string $builderValue = 'derived';

        public string $title = 'Validation title';

        public function configData(): array
        {
            return ['title' => $this->builderValue];
        }
    };
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $repository = new Repository(['craft' => ['custom' => $input]]);
    $app->instance('config', $repository);

    new ConfigServiceProvider($app)->register();

    expect($repository->get('craft.custom'))->toBe(['title' => 'derived'])
        ->and($input->validationData())->toBe(['title' => 'Validation title']);
});

it('does not rewrite the root without settings conversions', function (bool $missing) {
    $repository = Mockery::mock(Repository::class)->makePartial();
    if (! $missing) {
        $repository->set('craft', ['sparse' => ['foo' => null]]);
    }
    $repository->shouldNotReceive('set')->with('craft', Mockery::any());
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $app->instance('config', $repository);

    new ConfigServiceProvider($app)->register();

    expect($repository->get('craft.sparse'))->toBe($missing ? null : ['foo' => null]);
})->with([true, false]);

it('rejects malformed craft roots', function (mixed $root) {
    $app = new Application(sys_get_temp_dir().'/unused-plugin-config');
    $app->instance('config', new Repository(['craft' => $root]));

    expect(fn () => new ConfigServiceProvider($app)->register())
        ->toThrow(InvalidArgumentException::class, 'Configuration [craft] must be an array; got '.get_debug_type($root).'.');
})->with([null, false, 42, 'invalid']);

it('loads environment variables when configuration is cached', function () {
    $filesystem = new Filesystem;
    $basePath = sys_get_temp_dir().'/craft-cached-env-test-'.bin2hex(random_bytes(8));

    try {
        $filesystem->ensureDirectoryExists("$basePath/bootstrap/cache");
        $filesystem->put("$basePath/bootstrap/cache/config.php", '<?php return [];');
        $filesystem->put("$basePath/.env", 'CRAFT_CACHED_ENV_TEST=loaded-from-dotenv'.PHP_EOL);

        $app = new Application($basePath);
        $app->useEnvironmentPath($basePath);

        expect($app->configurationIsCached())->toBeTrue();
        expect(Env::parse('$CRAFT_CACHED_ENV_TEST'))->toBeNull();

        new ConfigServiceProvider($app)->register();

        expect($app->configurationIsCached())->toBeTrue();
        expect(Env::parse('$CRAFT_CACHED_ENV_TEST'))->toBe('loaded-from-dotenv');
    } finally {
        $filesystem->deleteDirectory($basePath);
    }
});

it('materializes array configuration through fluent setters', function () {
    app(ConfigRepository::class)->set('craft.general', [
        'extraAllowedFileExtensions' => ['CUSTOM'],
    ]);
    app()->forgetInstance(GeneralConfig::class);

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class);

    expect($config->assets->allowedFileExtensions)->toContain('custom')
        ->and($config->assets->extraAllowedFileExtensions)->toBeNull()
        ->and(app(ConfigRepository::class)->get('craft.general'))->toBe($config);
});

it('applies configured aliases and removes aliases with null paths', function (bool $fluent) {
    Aliases::set('@configExisting', '/previous');
    Aliases::set('@configReplaced', '/previous');

    $aliases = [
        '@configMissing' => null,
        '@configExisting' => null,
        '@configReplaced' => '/replacement',
        '@configEmpty' => '',
    ];
    app(ConfigRepository::class)->set('craft.general', $fluent
        ? GeneralConfig::create()->aliases($aliases)
        : ['aliases' => $aliases]);
    app()->forgetInstance(GeneralConfig::class);

    new ConfigServiceProvider(app())->register();
    app(GeneralConfig::class);

    expect(Aliases::get('@configMissing', false))->toBeFalse()
        ->and(Aliases::get('@configExisting', false))->toBeFalse()
        ->and(Aliases::get('@configReplaced'))->toBe('/replacement')
        ->and(Aliases::get('@configEmpty'))->toBe('');
})->with(['array' => false, 'fluent' => true]);

it('applies environment overrides when resolved', function () {
    app(ConfigRepository::class)->set('craft.general', [
        'cpTrigger' => 'control',
    ]);
    app()->forgetInstance(GeneralConfig::class);
    putenv('CRAFT_CP_TRIGGER=adminus');

    new ConfigServiceProvider(app())->register();

    expect(app(GeneralConfig::class)->cpTrigger)->toBe('adminus');
});

it('materializes typed MCP configuration from JSON environment overrides', function () {
    app(ConfigRepository::class)->set('craft.general', []);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_MCP'] = '{"instructions":"Be brief.","middleware":["throttle:mcp"]}';

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class);

    expect($config->mcp)->toBeInstanceOf(McpConfig::class)
        ->and($config->mcp->instructions)->toBe('Be brief.')
        ->and($config->mcp->middleware)->toBe(['throttle:mcp']);
});

it('materializes anchors configuration from general.php and JSON environment overrides', function () {
    app(ConfigRepository::class)->set('craft.general', ['anchors' => ['anchorLinkText' => '¶']]);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_ANCHORS'] = '{"anchorLinkPosition":"before","useAdditionalTagToAnchorTo":"false"}';

    new ConfigServiceProvider(app())->register();

    $anchors = app(GeneralConfig::class)->anchors;

    expect($anchors)->toBeInstanceOf(AnchorsConfig::class)
        ->and($anchors->anchorLinkText)->toBe('#')
        ->and($anchors->anchorLinkPosition)->toBe(AnchorsConfig::POS_BEFORE)
        ->and($anchors->useAdditionalTagToAnchorTo)->toBeFalse();
});

it('merges per-setting environment overrides into nested config objects', function () {
    app(ConfigRepository::class)->set('craft.general', ['mcp' => ['instructions' => 'From general.php.']]);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_MCP'] = '{"instructions":"From JSON.","middleware":["throttle:mcp"]}';
    $_SERVER['CRAFT_MCP_INSTRUCTIONS'] = 'From a single variable.';
    $_SERVER['CRAFT_ANCHORS_USE_ADDITIONAL_TAG_TO_ANCHOR_TO'] = 'false';

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class);

    expect($config->mcp)->toBeInstanceOf(McpConfig::class)
        ->and($config->mcp->instructions)->toBe('From a single variable.')
        ->and($config->mcp->middleware)->toBe(['throttle:mcp'])
        ->and($config->anchors->useAdditionalTagToAnchorTo)->toBeFalse();
});

it('materializes GraphQL configuration from general.php and environment overrides', function () {
    app(ConfigRepository::class)->set('craft.general', ['gql' => ['typePrefix' => 'site_', 'maxDepth' => 5]]);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_GQL'] = '{"enableIntrospection":false,"maxComplexity":500}';
    $_SERVER['CRAFT_GQL_MAX_DEPTH'] = '12';
    $_SERVER['CRAFT_MAX_GRAPHQL_COMPLEXITY'] = '300';

    new ConfigServiceProvider(app())->register();

    $gql = app(GeneralConfig::class)->gql;

    expect($gql)->toBeInstanceOf(GqlConfig::class)
        ->and($gql->typePrefix)->toBe('')
        ->and($gql->enableIntrospection)->toBeFalse()
        ->and($gql->maxComplexity)->toBe(500)
        ->and($gql->maxDepth)->toBe(12)
        ->and($gql->enabled)->toBeTrue();
});

it('forwards the renamed GraphQL settings to the gql setting', function () {
    app(ConfigRepository::class)->set('craft.general', ['enableGql' => false, 'gqlTypePrefix' => 'site_']);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_MAX_GRAPHQL_DEPTH'] = '7';
    $_SERVER['CRAFT_MAX_GRAPHQL_COMPLEXITY'] = '300';
    $_SERVER['CRAFT_GQL_MAX_COMPLEXITY'] = '500';

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class)
        ->enableGraphqlIntrospection(false)
        ->setGraphqlDatesToSystemTimeZone()
        ->maxGraphqlBatchSize('3');
    $config->maxGraphqlResults = 20;

    expect($config->gql->enabled)->toBeFalse()
        ->and($config->gql->typePrefix)->toBe('site_')
        ->and($config->gql->maxDepth)->toBe(7)
        ->and($config->gql->maxComplexity)->toBe(500)
        ->and($config->gql->enableIntrospection)->toBeFalse()
        ->and($config->gql->setDatesToSystemTimeZone)->toBeTrue()
        ->and($config->gql->maxResults)->toBe(20)
        ->and($config->gql->maxBatchSize)->toBe(3)
        ->and($config->enableGql)->toBeFalse()
        ->and($config['maxGraphqlDepth'])->toBe(7)
        ->and($config->gql->enableCaching)->toBeTrue()
        ->and($config->toArray())->not->toHaveKey('enableGql')
        ->and(GeneralConfig::fromConfig(['enableGql' => false])->gql->enabled)->toBeFalse()
        ->and(collect(Deprecator::getRequestLogs())->firstWhere('key', GeneralConfig::class.'::enableGraphqlIntrospection')?->file)->toBe(__FILE__);
});

it('forwards the renamed image, backup, and queue settings', function () {
    app(ConfigRepository::class)->set('craft.general', ['upscaleImages' => false, 'maxBackups' => false]);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_DEFAULT_IMAGE_QUALITY'] = '90';
    $_SERVER['CRAFT_QUEUE_NAME'] = 'craft';
    $_SERVER['CRAFT_BACKUP_COMMAND'] = 'false';

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class)->runQueueAutomatically(false);

    expect($config->images->upscale)->toBeFalse()
        ->and($config->images->defaultQuality)->toBe(90)
        ->and($config->backups->max)->toBeFalse()
        ->and($config->backups->command)->toBeFalse()
        ->and($config->getBackupOnUpdate())->toBeFalse()
        ->and($config->queue->name)->toBe('craft')
        ->and($config->queueName)->toBe('craft')
        ->and($config->queue->runAutomatically)->toBeFalse()
        ->and(collect(Deprecator::getRequestLogs())->pluck('key')->all())->not->toContain(GeneralConfig::class.'::queueName');
});

it('forwards the renamed asset settings through their fluent methods', function () {
    app(ConfigRepository::class)->set('craft.general', ['maxUploadFileSize' => '2M', 'revAssetUrls' => true]);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_EXTRA_ALLOWED_FILE_EXTENSIONS'] = 'MBOX,xml';
    $_SERVER['CRAFT_ASSETS_UPLOAD_CHUNK_SIZE'] = '1024';

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class);
    $config->tempAssetUploadDisk = 'temp-uploads';
    $config->allowedFileExtensions[] = 'zzz';

    expect($config->assets->maxUploadFileSize)->toBe(2097152)
        ->and($config->assets->revUrls)->toBeTrue()
        ->and($config->assets->allowedFileExtensions)->toContain('mbox', 'xml', 'jpg', 'zzz')
        ->and($config->assets->uploadChunkSize)->toBe(1024)
        ->and($config->assets->getTempUploadDisk())->toBe('temp-uploads');
});

it('forwards the renamed template and slug settings', function () {
    app(ConfigRepository::class)->set('craft.general', ['privateTemplateTrigger' => '', 'maxSlugIncrement' => 5]);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_DEFAULT_TEMPLATE_EXTENSIONS'] = 'blade.php,twig';
    $_SERVER['CRAFT_SLUG_WORD_SEPARATOR'] = '.';

    new ConfigServiceProvider(app())->register();

    $config = app(GeneralConfig::class)->enableTemplateCaching(false);

    expect($config->templates->privateTrigger)->toBe('')
        ->and($config->templates->defaultExtensions)->toBe(['blade.php', 'twig'])
        ->and($config->templates->enableCaching)->toBeFalse()
        ->and($config->slugs->maxIncrement)->toBe(5)
        ->and($config->slugs->wordSeparator)->toBe('.');
});

it('does not change a cached config’s nested objects when applying environment overrides', function () {
    $cached = GeneralConfig::create();
    app(ConfigRepository::class)->set('craft.general', $cached);
    app()->forgetInstance(GeneralConfig::class);
    $_SERVER['CRAFT_MCP_INSTRUCTIONS'] = 'Be brief.';
    $_SERVER['CRAFT_EXTRA_ALLOWED_FILE_EXTENSIONS'] = 'zzz';

    new ConfigServiceProvider(app())->register();

    expect(app(GeneralConfig::class)->mcp->instructions)->toBe('Be brief.')
        ->and($cached->mcp->instructions)->toBe('')
        ->and(app(GeneralConfig::class)->assets->allowedFileExtensions)->toContain('zzz')
        ->and($cached->assets->allowedFileExtensions)->not->toContain('zzz');
});

it('extends the default Twig sandbox allowlist with project entries', function () {
    app(ConfigRepository::class)->set('craft.twig-sandbox', [
        'allowedTags' => [],
        'allowedFilters' => ['projectFilter'],
        'allowedFunctions' => [],
        'allowedMethods' => [
            Collection::class => ['toJson'],
            ArrayObject::class => ['count'],
        ],
        'allowedProperties' => [],
    ]);

    new ConfigServiceProvider(app())->register();
    app()->forgetScopedInstances();

    $config = app(ConfigRepository::class)->get('craft.twig-sandbox');

    expect(app(TemplateManager::class)->renderSandboxedString('{% if true %}{{ "a&b"|e }}{% endif %}'))->toBe('a&amp;b')
        ->and($config['allowedFilters'])->toContain('e', 'projectFilter')
        ->and($config['allowedMethods'][Collection::class])->toContain('render', 'toJson')
        ->and($config['allowedMethods'][ArrayObject::class])->toBe(['count']);
});

it('fails when an environment override cannot be normalized', function () {
    app(ConfigRepository::class)->set('craft.general', [
        'defaultCountryCode' => 'US',
    ]);
    app()->forgetInstance(GeneralConfig::class);
    putenv('CRAFT_DEFAULT_COUNTRY_CODE=');

    new ConfigServiceProvider(app())->register();

    expect(fn () => app(GeneralConfig::class))
        ->toThrow(RuntimeException::class);

    expect(app(ConfigRepository::class)->get('craft.general'))->toBe([
        'defaultCountryCode' => 'US',
    ]);
});
