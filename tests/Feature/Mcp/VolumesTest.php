<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
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
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
});

it('manages volumes and their field layouts through MCP', function (): void {
    $call = fn (string $operation, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => "configuration.$operation",
        'arguments' => ['type' => 'volumes', ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');
    $created = $call('create', ['attributes' => [
        'name' => 'Documents',
        'handle' => 'documents',
        'fsHandle' => 'mcp-volumes',
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
            'fsHandle' => 'mcp-volumes',
        ])
        ->and($created['fieldLayout']['config']['tabs'][0]['name'])->toBe('Asset content')
        ->and($updated['name'])->toBe('Files')
        ->and($updated['hasUrls'])->toBeTrue()
        ->and($updated['fieldLayout']['config']['tabs'][0]['name'])->toBe('File content')
        ->and($fetched)->toBe($updated)
        ->and($deleted)->toBe(['type' => 'volumes', 'deleted' => true])
        ->and(Volume::query()->find($created['id']))->toBeNull();
});
