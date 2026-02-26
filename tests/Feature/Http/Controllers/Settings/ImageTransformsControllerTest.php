<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\ImageTransformsController;
use CraftCms\Cms\Image\Data\ImageTransform as ImageTransformData;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Image\Models\ImageTransform as ImageTransformModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
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
        throw new \RuntimeException('Failed to create image transform test fixture.');
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

it('requires authentication', function () {
    $transform = createTestTransform();
    Auth::logout();

    get(action([ImageTransformsController::class, 'index']))->assertRedirect();
    get(action([ImageTransformsController::class, 'create']))->assertRedirect();
    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => $transform->handle]))->assertRedirect();
    postJson(action([ImageTransformsController::class, 'save']))->assertUnauthorized();
    postJson(action([ImageTransformsController::class, 'delete']))->assertUnauthorized();
});

it('requires admin changes', function () {
    $transform = createTestTransform();
    Cms::config()->allowAdminChanges = false;

    get(action([ImageTransformsController::class, 'index']))
        ->assertOk()
        ->assertSee(t("Changes to these settings aren\u{2019}t permitted in this environment."));
    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => $transform->handle]))
        ->assertOk()
        ->assertSee(t("Changes to these settings aren\u{2019}t permitted in this environment."));

    get(action([ImageTransformsController::class, 'create']))->assertForbidden();
    postJson(action([ImageTransformsController::class, 'save']), validTransformData())->assertForbidden();
    postJson(action([ImageTransformsController::class, 'delete']), ['id' => $transform->id])->assertForbidden();
});

it('renders index', function () {
    get(action([ImageTransformsController::class, 'index']))
        ->assertOk()
        ->assertSee(t('New image transform'));
});

it('renders create', function () {
    get(action([ImageTransformsController::class, 'create']))
        ->assertOk()
        ->assertSee(t('Create a new image transform'));
});

it('renders edit for an existing transform', function () {
    $transform = createTestTransform();

    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => $transform->handle]))
        ->assertOk()
        ->assertSee($transform->name);
});

it('returns 404 for a missing transform handle', function () {
    get(action([ImageTransformsController::class, 'edit'], ['transformHandle' => 'missing-transform']))
        ->assertNotFound();
});

it('saves a new transform', function () {
    expect(ImageTransformModel::count())->toBe(0);

    $payload = validTransformData();

    postJson(action([ImageTransformsController::class, 'save']), $payload)
        ->assertOk()
        ->assertJsonPath('modelName', 'transform');

    expect(ImageTransformModel::count())->toBe(1);

    $service = app(ImageTransforms::class);
    $service->reset();
    $transform = $service->getTransformByHandle($payload['handle']);

    expect($transform)->not->toBeNull()
        ->and($transform->name)->toBe($payload['name']);
});

it('updates an existing transform', function () {
    $transform = createTestTransform([
        'name' => 'Original Name',
        'handle' => 'updatableTransform',
        'width' => 100,
    ]);

    postJson(action([ImageTransformsController::class, 'save']), validTransformData([
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
    postJson(action([ImageTransformsController::class, 'save']), validTransformData([
        'width' => '',
        'height' => '',
    ]))
        ->assertStatus(400)
        ->assertJsonPath('modelName', 'transform')
        ->assertJsonPath('errors.width.0', t('You must set at least one of the dimensions.'));
});

it('normalizes letterbox fill color on save', function () {
    $payload = validTransformData([
        'handle' => 'letterboxTransform',
        'mode' => 'letterbox',
        'fill' => 'abc',
    ]);

    postJson(action([ImageTransformsController::class, 'save']), $payload)->assertOk();

    $service = app(ImageTransforms::class);
    $service->reset();
    $transform = $service->getTransformByHandle($payload['handle']);

    expect($transform)->not->toBeNull()
        ->and($transform->fill)->toBe('#aabbcc');
});

it('deletes a transform', function () {
    $transform = createTestTransform();

    expect(ImageTransformModel::count())->toBe(1);

    postJson(action([ImageTransformsController::class, 'delete']), [
        'id' => $transform->id,
    ])->assertOk();

    expect(ImageTransformModel::count())->toBe(0);

    $service = app(ImageTransforms::class);
    $service->reset();
    expect($service->getTransformByHandle($transform->handle))->toBeNull();
});

it('validates required id on delete', function () {
    postJson(action([ImageTransformsController::class, 'delete']), [])
        ->assertJsonValidationErrors(['id']);
});
