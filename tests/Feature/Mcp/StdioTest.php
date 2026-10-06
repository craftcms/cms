<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Mcp\Events\CollectingAdminInstructions;
use CraftCms\Cms\Mcp\StdioTransport;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->serve = function (string $user, array $messages): array {
        $input = fopen('php://memory', 'r+');
        fwrite($input, implode('', array_map(static fn (array $message): string => json_encode($message).PHP_EOL, [
            ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => (object) [],
                'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
            ]],
            ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
            ...$messages,
        ])));
        rewind($input);
        $outputPath = tempnam(sys_get_temp_dir(), 'mcp-stdio');
        app()->bind(StdioTransport::class, fn ($app): StdioTransport => new StdioTransport($app, $input, fopen($outputPath, 'w')));

        $this->artisan('craft:mcp:serve', ['--user' => $user])->assertSuccessful();

        return collect(file($outputPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(static fn (string $line): array => json_decode($line, true))
            ->keyBy('id')
            ->all();
    };
});

it('serves the capabilities the named user may use, except HTTP-only capabilities', function (): void {
    Edition::set(Edition::Pro);
    Cms::config()->mcp(['instructions' => 'Preserve legal notices.']);
    Event::listen(CollectingAdminInstructions::class, function (CollectingAdminInstructions $event): void {
        $event->instructions[] = 'Use reviews.list before changing a product.';
    });

    $editor = User::factory()->withPermissions(['useCraftMcp'])->create(['username' => 'editor']);
    $call = static fn (string $tool, array $arguments = []): array => ['jsonrpc' => '2.0', 'id' => $tool, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $arguments]];

    $admin = ($this->serve)(User::query()->firstOrFail()->username, [$call('configuration.list', ['type' => 'sections']), $call('assets.upload.prepare')]);
    $editorResponses = ($this->serve)($editor->email, [$call('configuration.list', ['type' => 'sections'])]);

    expect($admin['configuration.list'])->not->toHaveKey('error')
        ->and($admin['init']['result']['instructions'])->toContain('hardDelete')
        ->toEndWith("Preserve legal notices.\n\nUse reviews.list before changing a product.")
        ->and($admin['configuration.list']['result']['structuredContent'])->toMatchArray(['type' => 'sections', 'items' => []])
        ->and($admin['assets.upload.prepare']['error']['message'])->toBe('Tool not found: "assets.upload.prepare".')
        ->and($editorResponses['configuration.list']['error']['message'])->toBe('Tool not found: "configuration.list".');
});

it('attributes every message’s activity to the named user and the MCP origin', function (): void {
    $user = User::query()->firstOrFail();
    $entry = Entry::factory()->createElement(['title' => 'Release notes']);
    $comment = static fn (string $id, string $markdown): array => [
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => 'tools/call',
        'params' => ['name' => 'activity.comments.create', 'arguments' => ['type' => 'entries', 'id' => $entry->id, 'markdown' => $markdown]],
    ];

    $responses = ($this->serve)((string) $user->id, [$comment('first', 'First'), $comment('second', 'Second')]);

    $events = ActivityEvent::query()->eventTypes(CommentCreated::class)->get();

    expect($responses['first']['result']['isError'])->toBeFalse()
        ->and($responses['second']['result']['isError'])->toBeFalse()
        ->and($events)->toHaveCount(2)
        ->and($events->pluck('actorId')->unique()->all())->toBe([$user->id])
        ->and($events->pluck('snapshots.origin')->all())->toBe(['MCP', 'MCP']);
});

it('refuses to serve users who may not use Craft MCP', function (Closure $user, string $message): void {
    Edition::set(Edition::Pro);
    app()->bind(StdioTransport::class, fn ($app): StdioTransport => new StdioTransport($app, fopen('php://memory', 'r'), fopen('php://memory', 'w')));

    $this->artisan('craft:mcp:serve', ['--user' => $user()])
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'missing option' => [fn (): string => '', 'The --user option is required.'],
    'unknown user' => [fn (): string => 'nobody', 'No user exists with the ID, username, or email [nobody].'],
    'suspended user' => [fn (): string => User::factory()->withPermissions(['useCraftMcp'])->create(['username' => 'suspended', 'suspended' => true])->username, 'The user [suspended] is not active.'],
    'missing permission' => [fn (): string => User::factory()->withPermissions(['accessCp'])->create(['username' => 'viewer'])->username, 'The user [viewer] does not have permission to use Craft MCP.'],
]);
