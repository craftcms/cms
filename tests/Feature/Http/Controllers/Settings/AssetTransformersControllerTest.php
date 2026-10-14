<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\AssetTransformersController;
use CraftCms\Cms\Http\ViewModels\AssetTransformerEditViewModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    app(AssetTransformers::class)->resolve('craft');
});

it('requires authentication', function () {
    Auth::logout();

    get(action([AssetTransformersController::class, 'index']))->assertRedirect();
    postJson(action([AssetTransformersController::class, 'store']))->assertUnauthorized();
});

it('gives its breadcrumbs links the Vue breadcrumbs can follow', function () {
    // Crumbs are `Cp\Data\ActionItem`s, which spell the link `url` — the same
    // as a nav item and the same as the legacy template has always read.
    get(action([AssetTransformersController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs', fn (Collection $crumbs): bool => $crumbs
                ->every(fn (array $crumb): bool => ! array_key_exists('url', $crumb))
            )
            ->where('crumbs.0.href', fn (?string $href): bool => is_string($href) && str_contains($href, 'settings'))
            ->etc()
        );
});

it('lists sorted transformers with editable default labels, handles, drivers, and permitted actions', function (bool $allowAdminChanges, string $defaultHandle) {
    Cms::config()->allowAdminChanges = $allowAdminChanges;
    app(AssetTransformDrivers::class)->extend('controller-test', fn () => new ControllerTestAssetTransformDriver);
    $remote = new AssetTransformer(['name' => 'A Remote', 'handle' => 'remote', 'driver' => 'controller-test']);
    $unavailable = new AssetTransformer(['name' => 'Z Unavailable', 'handle' => 'unavailable', 'driver' => 'craft']);
    app(AssetTransformers::class)->saveAssetTransformer($remote);
    app(AssetTransformers::class)->saveAssetTransformer($unavailable);
    app(ProjectConfig::class)->set(ProjectConfig::PATH_ASSET_TRANSFORMERS.'.'.$unavailable->uid.'.driver', 'missing-driver');
    Cms::config()->defaultAssetTransformer($defaultHandle);

    $response = get(action([AssetTransformersController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')->where('readOnly', ! $allowAdminChanges));
    $table = collect(flattenUiNodes($response->inertiaProps('ui.nodes')))->firstWhere('component', 'craft:admin-table')['props'];
    [$remoteRow, $craftRow, $unavailableRow] = $table['rows'];
    $handle = new Crawler($remoteRow['handle']['html'])->filter('craft-copy-attribute');

    expect(array_column(array_column($table['rows'], 'name'), 'label'))->toBe([
        $defaultHandle === 'remote' ? 'A Remote (Default)' : 'A Remote',
        $defaultHandle === 'craft' ? 'Craft (Default)' : 'Craft',
        'Z Unavailable',
    ])
        ->and($craftRow['name']['url'])->toBe(route('craft.cp.settings.assets.transformers.edit', ['handle' => 'craft']))
        ->and($remoteRow['id'])->toBe($remote->uid)
        ->and($handle->attr('value'))->toBe('remote')
        ->and($handle->text())->toBe('remote')
        ->and($remoteRow['driver'])->toBe('Controller test')
        ->and($unavailableRow['driver'])->toBe('missing-driver (Unavailable)')
        ->and($table['createUrl'])->toBe($allowAdminChanges ? action([AssetTransformersController::class, 'create']) : null)
        ->and($table['deletable'])->toBe($allowAdminChanges);

    if ($allowAdminChanges) {
        expect($craftRow['_deletable'])->toBeFalse()
            ->and($craftRow['_deleteDisabledReason'])->toBe('The Craft Asset Transformer cannot be deleted.')
            ->and($remoteRow['_deletable'])->toBe($defaultHandle !== 'remote')
            ->and($remoteRow['_deleteDisabledReason'])->toBe($defaultHandle === 'remote' ? 'This Asset Transformer cannot be deleted because it is configured as the default.' : null)
            ->and($remoteRow['_deleteUrl'])->toBe(action([AssetTransformersController::class, 'destroy'], ['handle' => 'remote']))
            ->and($remoteRow['_deleteConfirmMessage'])->toBe('Are you sure you want to delete the “A Remote” Asset Transformer?');
    } else {
        expect($craftRow)->not->toHaveKey('_deleteUrl')
            ->and($remoteRow)->not->toHaveKey('_deleteUrl');
    }
})->with([
    'Craft default' => [true, 'craft'],
    'custom default' => [true, 'remote'],
    'read-only' => [false, 'craft'],
]);

it('explains why transformers assigned to volumes cannot be deleted', function () {
    config()->set('filesystems.disks.controller-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/controller-test'),
    ]);
    $transformer = new AssetTransformer([
        'name' => 'Assigned',
        'handle' => 'assigned',
        'driver' => 'craft',
    ]);
    app(AssetTransformers::class)->saveAssetTransformer($transformer);
    app(Volumes::class)->saveVolume(new VolumeData([
        'name' => 'Assets',
        'handle' => 'assets',
        'fsHandle' => 'controller-test',
        'assetTransformer' => 'assigned',
    ]));

    $response = get(action([AssetTransformersController::class, 'index']))->assertOk();
    $table = collect(flattenUiNodes($response->inertiaProps('ui.nodes')))->firstWhere('component', 'craft:admin-table')['props'];
    $row = collect($table['rows'])->firstWhere('id', $transformer->uid);

    expect($row['_deletable'])->toBeFalse()
        ->and($row['_deleteDisabledReason'])->toBe('This Asset Transformer cannot be deleted because it is assigned to a volume.');
});

it('renders the standalone transformer UI', function () {
    get(action([AssetTransformersController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->where('ui.values.driver', 'craft')
            ->where('submit.url', action([AssetTransformersController::class, 'store']))
            ->where('refreshUrl', action([AssetTransformersController::class, 'renderUi'])));
});

it('stores driver settings on the Asset Transformer', function () {
    app(AssetTransformDrivers::class)->extend('controller-test', fn () => new ControllerTestAssetTransformDriver);

    postJson(action([AssetTransformersController::class, 'store']), [
        'name' => 'Remote',
        'handle' => 'remote',
        'driver' => 'controller-test',
        'settings' => [
            'endpoint' => 'https://images.example.test',
            'ignored' => 'discarded',
        ],
    ])->assertOk()->assertJsonPath('modelName', 'assetTransformer');

    expect(app(AssetTransformers::class)->resolve('remote')->settings)->toBe([
        'endpoint' => 'https://images.example.test',
    ]);
});

it('renders submitted driver settings with their validation errors', function (bool $readOnly) {
    app(AssetTransformDrivers::class)->extend('controller-test', fn () => new ControllerTestAssetTransformDriver);
    $transformer = new AssetTransformer([
        'name' => 'Remote',
        'handle' => 'remote',
        'driver' => 'controller-test',
        'settings' => ['endpoint' => 'https://saved.example.test'],
    ]);
    $transformer->errors()->add('name', 'Enter a name.');
    $transformer->errors()->add('endpoint', 'Enter a valid endpoint.');
    $transformer->errors()->add('unavailable', 'The remote service is unavailable.');
    $payload = new AssetTransformerEditViewModel(
        $transformer,
        app(AssetTransformDrivers::class),
        app(UiResolver::class),
        readOnly: $readOnly,
        values: ['name' => '', 'driver' => 'controller-test', 'settings' => ['endpoint' => 'invalid']],
    )->ui();
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $endpoint = $crawler->filter('craft-field[label="Endpoint"] input');

    expect($payload->scope)->toBe([])
        ->and($payload->refreshable)->toBe(! $readOnly)
        ->and($payload->values['name'])->toBe('')
        ->and($payload->values['settings'])->toBe(['endpoint' => 'invalid'])
        ->and($payload->errors)->toBe([
            ['path' => ['name'], 'messages' => ['Enter a name.']],
            ['path' => ['settings', 'endpoint'], 'messages' => ['Enter a valid endpoint.']],
        ])
        ->and($payload->globalErrors)->toBe(['The remote service is unavailable.'])
        ->and($endpoint->attr('name'))->toBe($readOnly ? null : 'settings[endpoint]')
        ->and($endpoint->attr('readonly') !== null)->toBe($readOnly)
        ->and($endpoint->attr('value'))->toBe('invalid')
        ->and($crawler->filter('craft-field[label="Endpoint"]')->text())->toContain('Enter a valid endpoint.');
})->with([false, true]);

it('renders stored settings for an unavailable transformer driver', function () {
    $payload = new AssetTransformerEditViewModel(
        new AssetTransformer([
            'name' => 'Remote',
            'driver' => 'missing-driver',
            'settings' => ['endpoint' => 'https://saved.example.test'],
        ]),
        app(AssetTransformDrivers::class),
        app(UiResolver::class),
    )->ui();
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $settings = $crawler->filter('craft-field[label="Stored settings"] textarea');

    expect(json_decode($settings->text(), true, flags: JSON_THROW_ON_ERROR))
        ->toBe(['endpoint' => 'https://saved.example.test'])
        ->and($settings->attr('readonly'))->not->toBeNull()
        ->and($settings->attr('name'))->toBeNull()
        ->and($crawler->text())->toContain('This Asset Transformer’s driver is unavailable.');
});

it('keeps the Craft transformer identity pinned', function () {
    $craft = app(AssetTransformers::class)->resolve('craft');

    postJson(action([AssetTransformersController::class, 'store']), [
        'uid' => $craft->uid,
        'name' => 'Changed',
        'handle' => 'changed',
        'driver' => 'craft',
        'settings' => [
            'subpath' => 'transforms',
            'generateTransformsBeforePageLoad' => true,
        ],
    ])->assertOk();

    $saved = app(AssetTransformers::class)->resolve('craft');
    expect($saved->name)->toBe('Craft')
        ->and($saved->driver)->toBe('craft')
        ->and($saved->settings['subpath'])->toBe('transforms')
        ->and($saved->settings['generateTransformsBeforePageLoad'])->toBeTrue();
});

it('deletes non-reserved transformers', function () {
    app(AssetTransformDrivers::class)->extend('controller-test', fn () => new ControllerTestAssetTransformDriver);
    postJson(action([AssetTransformersController::class, 'store']), [
        'name' => 'Disposable',
        'handle' => 'disposable',
        'driver' => 'controller-test',
    ])->assertOk();

    deleteJson(action([AssetTransformersController::class, 'destroy'], ['handle' => 'disposable']))->assertOk();

    expect(app(AssetTransformers::class)->getAssetTransformerByHandle('disposable'))->toBeNull();
});

it('respects read-only mode', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([AssetTransformersController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));
    get(action([AssetTransformersController::class, 'create']))->assertForbidden();
    postJson(action([AssetTransformersController::class, 'store']))->assertForbidden();
});

class ControllerTestAssetTransformDriver implements AssetTransformDriver
{
    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition('Controller test', settingsFields: [
            Field::make('Endpoint', Text::make('endpoint')),
        ]);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        return new AssetTransformResult('/unused', 'image/jpeg');
    }
}
