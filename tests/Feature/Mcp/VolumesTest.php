<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Mcp\Capabilities\Volumes;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\Storage;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

beforeEach(function () {
    config()->set('filesystems.disks.mcp-volumes', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/mcp-volumes'),
    ]);
    Storage::fake('mcp-volumes');
});

it('manages volumes through MCP', function () {
    $volumes = app(Volumes::class);
    $created = $volumes->create('Documents', 'documents', 'mcp-volumes')['volume'];
    $listed = $volumes->list();
    $request = new CallToolRequest('volumes.update', [
        'id' => $created['id'],
        'name' => 'Files',
        'hasUrls' => true,
    ]);
    $updated = $volumes->update(
        new RequestContext(new Session(new InMemorySessionStore), $request),
        id: $created['id'],
        name: 'Files',
        hasUrls: true,
    )['volume'];
    $fetched = $volumes->get(uid: $created['uid'])['volume'];

    app(ProjectConfig::class)->rebuild();
    $deleted = $volumes->delete(handle: 'documents');

    expect($listed['volumes'])->toHaveCount(1)
        ->and($listed['volumes'][0])->toMatchArray([
            'id' => $created['id'],
            'name' => 'Documents',
            'handle' => 'documents',
            'fsHandle' => 'mcp-volumes',
        ])
        ->and($updated['name'])->toBe('Files')
        ->and($updated['hasUrls'])->toBeTrue()
        ->and($fetched)->toBe($updated)
        ->and($deleted)->toBe(['deleted' => true])
        ->and(Volume::query()->find($created['id']))->toBeNull();
});
