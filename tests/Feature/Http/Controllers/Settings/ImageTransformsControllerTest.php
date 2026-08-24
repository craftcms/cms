<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessorDrivers;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Http\Controllers\Settings\ImageTransformsController;
use CraftCms\Cms\Image\Data\ImageTransform as ImageTransformData;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

function createTestTransform(array $overrides = []): ImageTransformData
{
    static $counter = 1;

    $data = array_merge([
        'name' => 'Test Transform',
        'handle' => 'testTransform'.$counter++,
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
        'position' => 'center-center',
        'interlace' => 'none',
    ], $overrides);

    $service = app(ImageTransforms::class);
    $service->saveTransform(new ImageTransformData($data));
    $service->reset();

    $transform = $service->getTransformByHandle($data['handle']);
    if (is_null($transform)) {
        throw new RuntimeException('Failed to create image transform test fixture.');
    }

    return $transform;
}

function validTransformData(array $overrides = []): array
{
    static $counter = 1;

    return array_merge([
        'name' => 'New Transform',
        'handle' => 'newTransform'.$counter++,
        'width' => 200,
        'height' => 200,
        'mode' => 'crop',
        'position' => 'center-center',
        'interlace' => 'none',
    ], $overrides);
}

function registerControllerAssetProcessor(string $driver = 'custom'): AssetProcessor
{
    app(AssetProcessorDrivers::class)->extend($driver, fn () => new ControllerAssetProcessorDriver);
    $transformer = new AssetProcessor([
        'name' => 'Custom',
        'handle' => $driver,
        'driver' => $driver,
    ]);
    app(AssetProcessors::class)->saveAssetProcessor($transformer);

    return $transformer;
}

it('requires authentication', function () {
    $transform = createTestTransform();
    Auth::logout();

    get(action([ImageTransformsController::class, 'index']))->assertRedirect();
    get(action([ImageTransformsController::class, 'create']))->assertRedirect();
    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => $transform->handle]))->assertRedirect();
    postJson(action([ImageTransformsController::class, 'renderForm']))->assertUnauthorized();
    postJson(action([ImageTransformsController::class, 'store']))->assertUnauthorized();
    deleteJson(action([ImageTransformsController::class, 'destroy'], [$transform->id]))->assertUnauthorized();
});

it('requires admin changes', function () {
    $transform = createTestTransform();
    Cms::config()->allowAdminChanges = false;

    get(action([ImageTransformsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('readOnly', true));
    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => $transform->handle]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/assets/transforms/Edit')
            ->where('readOnly', true));

    get(action([ImageTransformsController::class, 'create']))->assertForbidden();
    postJson(action([ImageTransformsController::class, 'renderForm']))->assertForbidden();
    postJson(action([ImageTransformsController::class, 'store']), validTransformData())->assertForbidden();
    deleteJson(action([ImageTransformsController::class, 'destroy'], [$transform->id]))->assertForbidden();
});

it('renders index', function () {
    get(action([ImageTransformsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/assets/transforms/Index'));
});

it('renders a functional create form', function () {
    get(action([ImageTransformsController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/assets/transforms/Edit')
            ->where('title', t('Create a new image transform'))
            ->where('form.values.transformId', null)
            ->where('form.values.mode', ImageTransformMode::Crop->value)
            ->where('submit.url', action([ImageTransformsController::class, 'store']))
            ->where('refreshUrl', action([ImageTransformsController::class, 'renderForm']))
            ->where('form.nodes', fn ($nodes): bool => collect($nodes)
                ->pluck('control.path')
                ->contains(['mode'])));
});

it('groups declared operation controls by Asset Processor', function () {
    $transformer = registerControllerAssetProcessor();

    get(action([ImageTransformsController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('form.nodes', fn ($nodes): bool => collect($nodes)
                ->pluck('children')
                ->flatten(1)
                ->pluck('control.path')
                ->contains(['operations', $transformer->uid, 'blur'])));
});

it('renders edit for an existing transform', function () {
    $transform = createTestTransform();

    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => $transform->handle]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/assets/transforms/Edit')
            ->where('title', $transform->name)
            ->where('form.values.transformId', $transform->id)
            ->where('form.values.name', $transform->name)
            ->where('form.values.handle', $transform->handle));
});

it('refreshes mode-dependent controls without saving', function () {
    $values = validTransformData([
        'transformId' => null,
        'fill' => 'abc',
        'quality' => '',
        'format' => '',
        'upscale' => true,
    ]);

    $fitNodes = postJson(action([ImageTransformsController::class, 'renderForm']), [
        'values' => [...$values, 'mode' => ImageTransformMode::Fit->value],
        'scope' => [],
    ])->assertOk()->json('form.nodes');
    $letterboxNodes = postJson(action([ImageTransformsController::class, 'renderForm']), [
        'values' => [...$values, 'mode' => ImageTransformMode::Letterbox->value],
        'scope' => [],
    ])->assertOk()->json('form.nodes');

    $fitFields = collect($fitNodes)->keyBy(fn (array $node): string => implode('.', $node['control']['path'] ?? []));
    $letterboxFields = collect($letterboxNodes)->keyBy(fn (array $node): string => implode('.', $node['control']['path'] ?? []));

    expect($fitFields['fill']['component'])->toBe('craft:hidden-field')
        ->and($fitFields['position']['component'])->toBe('craft:hidden-field')
        ->and($letterboxFields['fill']['component'])->toBe('craft:field')
        ->and($letterboxFields['position']['component'])->toBe('craft:field')
        ->and($letterboxFields['position']['props']['label'])->toBe(t('Image Position'))
        ->and(ImageTransformModel::count())->toBe(0);
});

it('rejects invalid refresh values', function () {
    postJson(action([ImageTransformsController::class, 'renderForm']), [
        'values' => validTransformData(['mode' => 'invalid']),
        'scope' => [],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('values.mode');
});

it('returns 404 for a missing transform handle', function () {
    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => 'missing-transform']))
        ->assertNotFound();
});

it('saves a new transform', function () {
    expect(ImageTransformModel::count())->toBe(0);

    $payload = validTransformData();

    postJson(action([ImageTransformsController::class, 'store']), $payload)
        ->assertOk()
        ->assertJsonPath('modelName', 'transform');

    expect(ImageTransformModel::count())->toBe(1);

    $service = app(ImageTransforms::class);
    $service->reset();
    $transform = $service->getTransformByHandle($payload['handle']);

    expect($transform)->not->toBeNull()
        ->and($transform->name)->toBe($payload['name']);
});

it('redirects to the saved transform edit page when saving and continuing', function () {
    $payload = validTransformData([
        'handle' => 'continuedTransform',
    ]);

    post(action([ImageTransformsController::class, 'store']), $payload)
        ->assertRedirect(Url::cpUrl('settings/assets/transforms/continuedTransform'))
        ->assertSessionHas('success', t('Transform saved.'));
});

it('redirects to the posted redirect when saving normally', function () {
    $payload = validTransformData([
        'handle' => 'normallySavedTransform',
        'redirect' => Crypt::encrypt('settings/assets/transforms'),
    ]);

    post(action([ImageTransformsController::class, 'store']), $payload)
        ->assertRedirect(Url::cpUrl('settings/assets/transforms'))
        ->assertSessionHas('success', t('Transform saved.'));
});

it('updates an existing transform', function () {
    $transform = createTestTransform([
        'name' => 'Original Name',
        'handle' => 'updatableTransform',
        'width' => 100,
    ]);

    postJson(action([ImageTransformsController::class, 'store']), validTransformData([
        'transformId' => $transform->id,
        'name' => 'Updated Name',
        'handle' => $transform->handle,
        'width' => 350,
        'height' => 120,
    ]))->assertOk();

    $service = app(ImageTransforms::class);
    $service->reset();
    $updated = $service->getTransformByHandle($transform->handle);

    expect($updated)->not->toBeNull()
        ->and($updated->name)->toBe('Updated Name')
        ->and($updated->width)->toBe(350)
        ->and($updated->height)->toBe(120);
});

it('rejects save when both width and height are missing', function () {
    post(action([ImageTransformsController::class, 'store']), validTransformData([
        'width' => '',
        'height' => '',
    ]))
        ->assertSessionHasErrors('width');
});

it('saves custom operations under the Asset Processor UUID', function () {
    $transformer = registerControllerAssetProcessor();
    $payload = validTransformData([
        'operations' => [$transformer->uid => ['blur' => '5']],
    ]);

    postJson(action([ImageTransformsController::class, 'store']), $payload)->assertOk();

    app(ImageTransforms::class)->reset();
    $transform = app(ImageTransforms::class)->getTransformByHandle($payload['handle']);

    expect($transform->getCustomOperations())->toBe([
        $transformer->uid => ['blur' => '5'],
    ]);
});

it('preserves operations for a configured unavailable driver', function () {
    $assetProcessor = new AssetProcessor([
        'name' => 'Unavailable',
        'handle' => 'unavailable',
        'driver' => 'missing',
    ]);
    app(AssetProcessors::class)->saveAssetProcessor($assetProcessor, runValidation: false);
    $transform = new ImageTransformData([
        'name' => 'Unavailable',
        'handle' => 'unavailable',
        'width' => 100,
        'operations' => [$assetProcessor->uid => ['blur' => 5]],
    ]);
    app(ImageTransforms::class)->saveTransform($transform, runValidation: false);

    postJson(action([ImageTransformsController::class, 'store']), validTransformData([
        'transformId' => $transform->id,
        'handle' => 'unavailable',
    ]))->assertOk();

    app(ImageTransforms::class)->reset();

    expect(app(ImageTransforms::class)->getTransformByHandle('unavailable')
        ?->getOperationsForTransformer($assetProcessor->uid))->toBe(['blur' => 5]);
});

it('normalizes letterbox fill color on save', function () {
    $payload = validTransformData([
        'handle' => 'letterboxTransform',
        'mode' => 'letterbox',
        'fill' => 'abc',
    ]);

    postJson(action([ImageTransformsController::class, 'store']), $payload)->assertOk();

    $service = app(ImageTransforms::class);
    $service->reset();
    $transform = $service->getTransformByHandle($payload['handle']);

    expect($transform)->not->toBeNull()
        ->and($transform->fill)->toBe('#aabbcc');
});

it('deletes a transform', function () {
    $transform = createTestTransform();

    expect(ImageTransformModel::count())->toBe(1);

    deleteJson(action([ImageTransformsController::class, 'destroy'], [$transform->id]))->assertOk();

    expect(ImageTransformModel::count())->toBe(0);

    $service = app(ImageTransforms::class);
    $service->reset();
    expect($service->getTransformByHandle($transform->handle))->toBeNull();
});

class ControllerAssetProcessorDriver implements AssetProcessorDriver
{
    public function definition(): AssetProcessorDriverDefinition
    {
        return new AssetProcessorDriverDefinition(
            'Custom',
            operations: ['blur' => ['integer', 'min:1']],
            operationFields: [
                'blur' => Field::make(t('Blur'), Number::make('blur')->min(1)),
            ],
        );
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        return new AssetTransformResult('/custom.webp', 'image/webp');
    }
}
