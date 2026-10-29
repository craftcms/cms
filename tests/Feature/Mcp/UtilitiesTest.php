<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Queue\JobProgress;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');

    $this->call = fn (string $name, array $arguments = []): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => $name,
        'arguments' => $arguments,
    ])->assertOk();
    $this->result = fn (string $name, array $arguments = []): array => ($this->call)($name, $arguments)
        ->assertJsonPath('result.isError', false)
        ->json('result.structuredContent');
});

afterEach(function (): void {
    ClearCaches::flushState();
    app()->maintenanceMode()->deactivate();
});

it('lists and clears caches', function (): void {
    $cleared = [];
    ClearCaches::add('widgets', ['label' => 'Widget caches', 'info' => 'Rendered widgets.', 'action' => function () use (&$cleared) {
        $cleared[] = 'widgets';
    }]);
    ClearCaches::add('gadgets', ['label' => 'Gadget caches', 'action' => function () use (&$cleared) {
        $cleared[] = 'gadgets';
    }]);

    expect(($this->result)('caches.list')['caches'])->toBe([
        ['key' => 'gadgets', 'label' => 'Gadget caches', 'info' => null],
        ['key' => 'widgets', 'label' => 'Widget caches', 'info' => 'Rendered widgets.'],
    ])
        ->and(($this->result)('caches.clear', ['caches' => ['widgets']]))->toBe(['cleared' => ['widgets']])
        ->and($cleared)->toBe(['widgets'])
        ->and(($this->result)('caches.clear')['cleared'])->toEqualCanonicalizing(['gadgets', 'widgets']);

    ($this->call)('caches.clear', ['caches' => ['nope']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Unknown cache keys: nope. Call caches.list for the available keys.');
});

it('reports, runs, retries and releases queue jobs', function (): void {
    app(JobProgress::class)->queued('job-1', 'Resaving entries');
    app(JobProgress::class)->queued('job-2', 'Updating search indexes');
    Artisan::partialMock()->shouldReceive('call')->once()->with('queue:retry', ['id' => 'all']);

    $status = ($this->result)('queue.status');

    expect($status['total'])->toBe(2)
        ->and(array_column($status['jobs'], 'description'))->toEqualCanonicalizing(['Resaving entries', 'Updating search indexes'])
        ->and(($this->result)('queue.retry-all'))->toBe(['retried' => true])
        ->and(($this->result)('queue.release-all'))->toBe(['released' => true])
        ->and(app(JobProgress::class)->getTotalJobs())->toBe(0)
        ->and(($this->result)('queue.run'))->toBe(['remaining' => 0]);
});

it('runs pending queue jobs for a limited time', function (): void {
    app(JobProgress::class)->queued('job-1', 'Resaving entries');
    Artisan::partialMock()->shouldReceive('call')->once()->withArgs(
        fn (string $command, array $options): bool => $command === 'queue:work'
            && $options['--stop-when-empty'] === true
            && $options['--max-time'] === 10,
    );

    expect(($this->result)('queue.run', ['maxSeconds' => 10]))->toBe(['remaining' => 1]);
});

it('updates the indexes of the given volumes', function (): void {
    Volume::factory()->create(['handle' => 'photos']);
    Artisan::partialMock()->shouldReceive('call')->once()->with('craft:index-assets:one', [
        'handle' => 'photos',
        '--createMissingAssets' => 'true',
        '--deleteMissingAssets' => 'false',
        '--deleteEmptyFolders' => 'false',
        '--no-interaction' => true,
    ]);
    Artisan::partialMock()->shouldReceive('output')->andReturn("Indexed photos\n");

    expect(($this->result)('asset-indexes.update', ['volumes' => ['photos']]))->toBe([
        'volumes' => ['photos'],
        'output' => 'Indexed photos',
    ]);

    ($this->call)('asset-indexes.update', ['volumes' => ['missing']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Volume [missing] not found.');
});

it('only offers utility tools to users who can use those utilities', function (): void {
    $user = User::factory()->withPermissions(['accessCp', 'utility:clear-caches'])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:write'], 'craft-mcp');

    $tools = array_column(McpRequest::tools($this), 'name');

    expect($tools)->toContain('caches.clear')
        ->not->toContain('queue.release-all')
        ->not->toContain('asset-indexes.update')
        ->not->toContain('maintenance-mode.enable')
        ->not->toContain('project-config.rebuild');
});

it('refuses utilities that are disabled', function (): void {
    Cms::config()->disabledUtilities(['clear-caches']);

    ($this->call)('caches.clear')
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'The clear-caches utility is disabled.');
});

it('turns maintenance mode on and off, staying reachable while it’s on', function (): void {
    expect(($this->result)('maintenance-mode.enable'))->toBe(['enabled' => true])
        ->and(app()->maintenanceMode()->active())->toBeTrue()
        ->and(($this->result)('maintenance-mode.get'))->toBe(['enabled' => true])
        ->and(($this->result)('maintenance-mode.disable'))->toBe(['enabled' => false])
        ->and(app()->maintenanceMode()->active())->toBeFalse();
});

it('only lets users who can access the system while it’s off use MCP during maintenance', function (array $permissions, int $status): void {
    $user = User::factory()->withPermissions(['accessCp', ...$permissions])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:read'], 'craft-mcp');
    app()->maintenanceMode()->activate([]);

    McpRequest::send($this, 'tools/list')->assertStatus($status);
})->with([
    'without access' => [[], 503],
    'with control panel access only' => [['accessCpWhenSystemIsOff'], 503],
    'with site access' => [['accessSiteWhenSystemIsOff'], 200],
]);

it('rebuilds the project config, unless it’s read-only', function (): void {
    expect(($this->result)('project-config.rebuild'))->toBe(['rebuilt' => true]);

    app(ProjectConfig::class)->readOnly = true;

    ($this->call)('project-config.rebuild')
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Rebuilding the project config is not allowed while it’s in read-only mode.');
});
