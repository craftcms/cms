<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    config()->set('filesystems.disks.mcp-volumes', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/mcp-volumes'),
    ]);
    Storage::fake('mcp-volumes');
    $key = passportKey();
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
});

it('manages volumes and their field layouts through MCP', function (): void {
    $call = fn (string $operation, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => "configuration_$operation",
        'arguments' => ['type' => 'volumes', ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');
    $created = $call('create', ['attributes' => [
        'name' => 'Documents',
        'handle' => 'documents',
        'disk' => 'mcp-volumes',
        'fieldLayout' => ['tabs' => [['name' => 'Asset content', 'elements' => []]]],
    ]])['item'];
    $listed = $call('list', []);
    $updated = $call('update', [
        'identifier' => ['id' => $created['id']],
        'attributes' => [
            'name' => 'Files',
            'hasUrls' => true,
            'fieldLayout' => ['tabs' => [['name' => 'File content', 'elements' => []]]],
        ],
    ])['item'];
    $fetched = $call('get', ['identifier' => ['uid' => $created['uid']]])['item'];

    app(ProjectConfig::class)->rebuild();
    $deleted = $call('delete', ['identifier' => ['handle' => 'documents']]);

    expect($listed['items'])->toHaveCount(1)
        ->and($listed['items'][0])->toMatchArray([
            'id' => $created['id'],
            'name' => 'Documents',
            'handle' => 'documents',
            'disk' => 'mcp-volumes',
        ])
        ->and($created['fieldLayout']['config']['tabs'][0]['name'])->toBe('Asset content')
        ->and($updated['name'])->toBe('Files')
        ->and($updated['hasUrls'])->toBeTrue()
        ->and($updated['fieldLayout']['config']['tabs'][0]['name'])->toBe('File content')
        ->and($fetched)->toBe($updated)
        ->and($deleted)->toBe(['type' => 'volumes', 'deleted' => true])
        ->and(Volume::query()->find($created['id']))->toBeNull();
});

it('accepts the deprecated fsHandle parameter', function (): void {
    config()->set('filesystems.disks.mcp-volumes-other', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/mcp-volumes-other'),
    ]);
    $call = fn (string $operation, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => "configuration_$operation",
        'arguments' => ['type' => 'volumes', ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');
    $created = $call('create', ['attributes' => [
        'name' => 'Documents',
        'handle' => 'documents',
        'fsHandle' => 'mcp-volumes',
    ]])['item'];
    $updated = $call('update', [
        'identifier' => ['id' => $created['id']],
        'attributes' => ['fsHandle' => 'mcp-volumes-other'],
    ])['item'];

    expect($created['disk'])->toBe('mcp-volumes')
        ->and($updated['disk'])->toBe('mcp-volumes-other')
        ->and(Volume::query()->find($created['id'])->disk)->toBe('mcp-volumes-other')
        ->and(collect(Deprecator::getRequestLogs())->pluck('key'))->toContain('mcp.volumes.fsHandle');
});
