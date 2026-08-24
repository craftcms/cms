<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\Controllers\Settings\AssetProcessorsController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    app(AssetProcessors::class)->resolve('craft');
});

it('requires authentication', function () {
    Auth::logout();

    get(action([AssetProcessorsController::class, 'index']))->assertRedirect();
    postJson(action([AssetProcessorsController::class, 'store']))->assertUnauthorized();
});

it('lists the required Craft processor', function () {
    get(action([AssetProcessorsController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/assets/processors/Index')
            ->where('processors', fn ($processors): bool => collect($processors)
                ->contains(fn (array $processor): bool => $processor['handle'] === 'craft'
                    && $processor['isDefault'] === true
                    && $processor['canDelete'] === false)));
});

it('renders the standalone processor form', function () {
    get(action([AssetProcessorsController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Form')
            ->where('form.values.driver', 'craft')
            ->where('submit.url', action([AssetProcessorsController::class, 'store']))
            ->where('refreshUrl', action([AssetProcessorsController::class, 'renderForm'])));
});

it('stores driver settings on the Asset Processor', function () {
    app(AssetTransformDrivers::class)->extend('controller-test', fn () => new ControllerTestAssetTransformDriver);

    postJson(action([AssetProcessorsController::class, 'store']), [
        'name' => 'Remote',
        'handle' => 'remote',
        'driver' => 'controller-test',
        'settings' => [
            'endpoint' => 'https://images.example.test',
            'ignored' => 'discarded',
        ],
    ])->assertOk()->assertJsonPath('modelName', 'assetProcessor');

    expect(app(AssetProcessors::class)->resolve('remote')->settings)->toBe([
        'endpoint' => 'https://images.example.test',
    ]);
});

it('keeps the Craft processor identity pinned', function () {
    $craft = app(AssetProcessors::class)->resolve('craft');

    postJson(action([AssetProcessorsController::class, 'store']), [
        'uid' => $craft->uid,
        'name' => 'Changed',
        'handle' => 'changed',
        'driver' => 'craft',
        'settings' => ['subpath' => 'renditions'],
    ])->assertOk();

    $saved = app(AssetProcessors::class)->resolve('craft');
    expect($saved->name)->toBe('Craft')
        ->and($saved->driver)->toBe('craft')
        ->and($saved->settings['subpath'])->toBe('renditions');
});

it('deletes non-reserved processors', function () {
    app(AssetTransformDrivers::class)->extend('controller-test', fn () => new ControllerTestAssetTransformDriver);
    postJson(action([AssetProcessorsController::class, 'store']), [
        'name' => 'Disposable',
        'handle' => 'disposable',
        'driver' => 'controller-test',
    ])->assertOk();

    deleteJson(action([AssetProcessorsController::class, 'destroy'], ['handle' => 'disposable']))->assertOk();

    expect(app(AssetProcessors::class)->getAssetProcessorByHandle('disposable'))->toBeNull();
});

it('respects read-only mode', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([AssetProcessorsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));
    get(action([AssetProcessorsController::class, 'create']))->assertForbidden();
    postJson(action([AssetProcessorsController::class, 'store']))->assertForbidden();
});

class ControllerTestAssetTransformDriver implements AssetTransformDriver
{
    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition('Controller test', settings: [
            Field::make('Endpoint', Text::make('endpoint')),
        ]);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        return new AssetTransformResult('/unused', 'image/jpeg');
    }
}
