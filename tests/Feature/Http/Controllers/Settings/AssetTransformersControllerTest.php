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
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\Controllers\Settings\AssetTransformersController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

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

it('lists the required Craft transformer', function () {
    get(action([AssetTransformersController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/assets/transformers/Index')
            ->where('transformers', fn ($transformers): bool => collect($transformers)
                ->contains(fn (array $transformer): bool => $transformer['handle'] === 'craft'
                    && $transformer['isDefault'] === true
                    && $transformer['deleteDisabledReason'] === 'The Craft Asset Transformer cannot be deleted.')));
});

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
        'fsHandle' => 'disk:controller-test',
        'assetTransformer' => 'assigned',
    ]));

    get(action([AssetTransformersController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('transformers', fn ($transformers): bool => collect($transformers)
                ->contains(fn (array $transformer): bool => $transformer['handle'] === 'assigned'
                    && $transformer['deleteDisabledReason'] === 'This Asset Transformer cannot be deleted because it is assigned to a volume.')));
});

it('renders the standalone transformer form', function () {
    get(action([AssetTransformersController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Form')
            ->where('form.values.driver', 'craft')
            ->where('submit.url', action([AssetTransformersController::class, 'store']))
            ->where('refreshUrl', action([AssetTransformersController::class, 'renderForm'])));
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
