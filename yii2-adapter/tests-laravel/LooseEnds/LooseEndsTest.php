<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use craft\config\GeneralConfig as LegacyGeneralConfig;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Yii2Adapter\Database\DeprecatedTable;
use CraftCms\Yii2Adapter\LooseEnds\LegacyUsage;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsChecks;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsStore;
use CraftCms\Yii2Adapter\LooseEnds\LooseEndsUtility;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function CraftCms\Cms\template;

uses(DatabaseTestCase::class);

it('records legacy usage in project templates at the template’s path and line', function(): void {
    $file = base_path('templates/_loose-ends-test.twig');
    @mkdir(dirname($file), recursive: true);
    file_put_contents($file, "<p>\n{{ craft.app.id }}\n</p>");

    try {
        template('_loose-ends-test', templateMode: TemplateMode::Site);
    } finally {
        unlink($file);
    }

    expect(collect(app(LegacyUsage::class)->pending())->firstWhere('key', 'craft-variable.app'))
        ->toMatchArray(['file' => 'templates/_loose-ends-test.twig', 'line' => 2]);
});

it('ignores legacy usage from installed packages', function(): void {
    $usage = app(LegacyUsage::class);

    $usage->add('vendor', 'Used by a package.', InstalledVersions::getInstallPath('guzzlehttp/guzzle') . '/src/Client.php', 10);
    $usage->add('project', 'Used by the project.', base_path('modules/Module.php'), 10);

    expect(array_column($usage->pending(), 'key'))->toBe(['project']);
});

it('deletes usage marked resolved, and records it again if it’s seen again', function(): void {
    $this->actingAs(User::factory()->admin()->create());
    $usage = app(LegacyUsage::class);
    $store = app(LooseEndsStore::class);
    $record = function() use ($usage): void {
        $usage->add('craft-variable.app', '`craft.app` is used.', base_path('templates/index.twig'), 3);
        $usage->flush();
    };

    $record();
    $id = $store->usages()->sole()->id;

    $this->post(route('craft.cp.utilities.yii2-loose-ends.resolve', ['id' => $id]))->assertRedirect();

    expect(DB::table(LooseEndsStore::TABLE)->count())->toBe(0);

    $record();

    expect($store->usages())->toHaveCount(1);
});

it('drops unused category support with “Fix this” only where admin changes are allowed', function(): void {
    foreach ([DeprecatedTable::CATEGORIES, DeprecatedTable::CATEGORYGROUPS, DeprecatedTable::CATEGORYGROUPS_SITES] as $table) {
        Schema::create($table, fn(Blueprint $blueprint) => $blueprint->increments('id'));
    }

    $this->actingAs(User::factory()->admin()->create());
    $fix = fn() => $this->post(route('craft.cp.utilities.yii2-loose-ends.fix'), ['key' => 'content.categories']);

    Cms::config()->allowAdminChanges = false;
    $fix()->assertForbidden();

    expect(Schema::hasTable(DeprecatedTable::CATEGORIES))->toBeTrue();

    Cms::config()->allowAdminChanges = true;
    $fix()->assertRedirect();

    expect(Schema::hasTable(DeprecatedTable::CATEGORIES))->toBeFalse()
        ->and(Schema::hasTable(DeprecatedTable::CATEGORYGROUPS))->toBeFalse();
});

it('reports each removed general config setting that is set', function(string $method, mixed $value): void {
    app()->instance(GeneralConfig::class, LegacyGeneralConfig::create()->{$method}($value));

    expect(app(LooseEndsChecks::class)->generalConfigSettings()[0]->detail ?? '')
        ->toContain("`$method`:");
})->with(function(): iterable {
    $samples = [
        'bool' => true,
        'int' => 7,
        'string' => 'value',
        'array' => ['value'],
        'mixed' => 1234,
    ];

    foreach (new ReflectionClass(LegacyGeneralConfig::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getAttributes(Deprecated::class) === [] || $method->name === 'tempAssetUploadFs') {
            continue;
        }

        $type = $method->getParameters()[0]->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        $name = collect($types)->map(fn(ReflectionNamedType $type) => $type->getName())->first(fn(string $name) => isset($samples[$name]));

        yield $method->name => [$method->name, $samples[$name]];
    }
});

it('still reports removed settings once the config is cached', function(): void {
    $state = var_export(LegacyGeneralConfig::create()->enableCsrfProtection(true), true);
    eval('$restored = ' . $state . ';');
    app()->instance(GeneralConfig::class, $restored);

    expect(app(LooseEndsChecks::class)->generalConfigSettings()[0]->detail ?? '')
        ->toContain('`enableCsrfProtection`:');
});

it('shows the removal steps once nothing depends on the adapter', function(): void {
    $html = LooseEndsUtility::contentHtml();

    expect($html)
        ->toContain('Your project no longer depends on the Yii2 adapter.')
        ->toContain('php craft yii2-adapter:cleanup')
        ->toContain('composer remove craftcms/yii2-adapter');

    $this->artisan('craft:yii2-adapter:cleanup')->assertSuccessful();

    expect(Schema::hasTable(LooseEndsStore::TABLE))->toBeFalse();
});
