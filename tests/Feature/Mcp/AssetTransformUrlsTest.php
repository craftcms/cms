<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Image\Jobs\GenerateImageTransform;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

beforeEach(function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    config()->set('filesystems.disks.transform-preview', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/transform-preview'),
    ]);
    Storage::fake('transform-preview');
    Queue::fake();

    $this->volume = Volume::factory()->create(['fs' => 'transform-preview']);
    $this->asset = Asset::factory()->createElement([
        'volumeId' => $this->volume->id,
        'filename' => 'source.jpg',
        'width' => 800,
        'height' => 400,
    ]);
});

it('returns usable preview URLs through MCP for named and inline transforms', function (
    array|string $transform,
    ?string $transformer,
): void {
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'name' => 'Preview',
        'handle' => 'preview',
        'driver' => 'craft',
        'settings' => ['generateTransformsBeforePageLoad' => true],
    ]));
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'Thumbnail',
        'handle' => 'thumbnail',
        'width' => 160,
        'height' => 80,
        'format' => 'png',
    ]));
    Storage::disk('transform-preview')->put(
        $this->asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/google.png'),
    );

    $result = McpRequest::send($this, 'tools/call', [
        'name' => 'assets.transform-url',
        'arguments' => array_filter([
            'uid' => $this->asset->uid,
            'transform' => $transform,
            'transformer' => $transformer,
        ], static fn (mixed $value): bool => $value !== null),
    ])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.mimeType', 'image/png')
        ->assertJsonPath('result.structuredContent.width', 160)
        ->assertJsonPath('result.structuredContent.height', 80)
        ->json('result.structuredContent');

    Queue::assertPushedTimes(GenerateImageTransform::class, $transformer === null ? 1 : 0);

    $response = $this->get($result['url'])
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
    $dimensions = getimagesizefromstring($response->streamedContent());

    expect($dimensions[0])->toBe(160)
        ->and($dimensions[1])->toBe(80);
})->with([
    'named transform with deferred generation' => ['thumbnail', null],
    'inline transform with an immediate transformer override' => [['width' => 160, 'height' => 80, 'format' => 'png'], 'preview'],
]);

it('requires asset view permission before generating a preview', function (): void {
    $user = User::query()->firstOrFail();
    $user->update(['admin' => false]);
    $permissions = ['accessCp', 'useCraftMcp'];
    app(UserPermissions::class)->saveUserPermissions($user->id, $permissions);
    Passport::actingAs($user, ['mcp:use'], 'craft-mcp');
    Cms::config()->allowAdminChanges = false;
    $params = [
        'name' => 'assets.transform-url',
        'arguments' => ['id' => $this->asset->id, 'transform' => ['width' => 160]],
    ];

    McpRequest::send($this, 'tools/call', $params)
        ->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Asset not found.');

    Queue::assertNothingPushed();

    app(UserPermissions::class)->saveUserPermissions($user->id, [
        ...$permissions,
        "viewAssets:{$this->volume->uid}",
        "viewPeerAssets:{$this->volume->uid}",
    ]);

    McpRequest::send($this, 'tools/call', $params)
        ->assertOk()
        ->assertJsonPath('result.isError', false);

    Queue::assertPushed(GenerateImageTransform::class);
});

it('converts transform validation failures into MCP tool errors', function (): void {
    McpRequest::send($this, 'tools/call', [
        'name' => 'assets.transform-url',
        'arguments' => ['id' => $this->asset->id, 'transform' => ['width' => 0]],
    ])
        ->assertOk()
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Invalid Asset Transform parameter value.');
});
