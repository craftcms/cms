<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\EventTypes\CommentCreated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Mcp\CapabilityRegistry;
use CraftCms\Cms\Mcp\Events\CollectingAdminInstructions;
use CraftCms\Cms\Mcp\StdioTransport;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Tests\Support\McpCapabilities\Example;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->serve = function (string $user, array $messages): array {
        $input = fopen('php://memory', 'r+');
        fwrite($input, implode('', array_map(static fn (array $message): string => Json::encode($message).PHP_EOL, [
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
            ->map(static fn (string $line): array => Json::decode($line))
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

    app(CapabilityRegistry::class)->register(Example::class);
    $editor = User::factory()->withPermissions([])->create(['username' => 'editor']);
    $call = static fn (string $tool, array $arguments = []): array => ['jsonrpc' => '2.0', 'id' => $tool, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $arguments]];

    $admin = ($this->serve)(User::query()->where('admin', true)->firstOrFail()->username, [$call('info_get'), $call('configuration_list', ['type' => 'sections']), $call('example_http')]);
    $editorResponses = ($this->serve)($editor->email, [$call('configuration_list', ['type' => 'sections'])]);

    expect($admin['configuration_list'])->not->toHaveKey('error')
        ->and(strlen($admin['init']['result']['instructions']))->toBeLessThanOrEqual(2048)
        ->and($admin['init']['result']['instructions'])->toContain('info_get')
        ->not->toContain('Preserve legal notices.', 'reviews.list')
        ->and($admin['info_get']['result']['structuredContent']['instructions'])->toContain('hardDelete')
        ->toEndWith("Preserve legal notices.\n\nUse reviews.list before changing a product.")
        ->and($admin['configuration_list']['result']['structuredContent'])->toMatchArray(['type' => 'sections', 'items' => []])
        ->and($admin['example_http']['error']['message'])->toBe('Tool not found: "example_http".')
        ->and($editorResponses['configuration_list']['error']['message'])->toBe('Tool not found: "configuration_list".');
});

it('attributes every message’s activity to the named user and the MCP origin', function (): void {
    $user = User::query()->firstOrFail();
    $entry = Entry::factory()->createElement(['title' => 'Release notes']);
    $comment = static fn (string $id, string $markdown): array => [
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => 'tools/call',
        'params' => ['name' => 'activity_comments_create', 'arguments' => ['type' => 'entries', 'id' => $entry->id, 'markdown' => $markdown]],
    ];

    $responses = ($this->serve)((string) $user->id, [$comment('first', 'First'), $comment('second', 'Second')]);

    $events = ActivityEvent::query()->eventTypes(CommentCreated::class)->get();

    expect($responses['first']['result']['isError'])->toBeFalse()
        ->and($responses['second']['result']['isError'])->toBeFalse()
        ->and($events)->toHaveCount(2)
        ->and($events->pluck('actorId')->unique()->all())->toBe([$user->id])
        ->and($events->pluck('snapshots.origin')->all())->toBe(['MCP', 'MCP']);
});

it('refuses to serve missing or inactive users', function (Closure $user, string $message): void {
    Edition::set(Edition::Pro);
    app()->bind(StdioTransport::class, fn ($app): StdioTransport => new StdioTransport($app, fopen('php://memory', 'r'), fopen('php://memory', 'w')));

    $this->artisan('craft:mcp:serve', ['--user' => $user()])
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'missing option' => [fn (): string => '', 'The --user option is required.'],
    'unknown user' => [fn (): string => 'nobody', 'No user exists with the ID, username, or email [nobody].'],
    'suspended user' => [fn (): string => User::factory()->withPermissions([])->create(['username' => 'suspended', 'suspended' => true])->username, 'The user [suspended] is not active.'],
]);

it('saves project config changes before responding, rather than when the server stops', function (): void {
    $input = fopen('php://memory', 'r+');
    fwrite($input, implode('', array_map(static fn (array $message): string => Json::encode($message).PHP_EOL, [
        ['jsonrpc' => '2.0', 'id' => 'init', 'method' => 'initialize', 'params' => [
            'protocolVersion' => '2025-11-25',
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'stdio-test', 'version' => '1.0'],
        ]],
        ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'],
        ['jsonrpc' => '2.0', 'id' => 'create', 'method' => 'tools/call', 'params' => ['name' => 'configuration_create', 'arguments' => ['type' => 'site-groups', 'attributes' => ['name' => 'Archive']]]],
    ])));
    rewind($input);
    StdioTestOutput::$saved = [];
    stream_wrapper_register('stdio-test-output', StdioTestOutput::class);
    $this->beforeApplicationDestroyed(fn () => stream_wrapper_unregister('stdio-test-output'));
    app()->bind(StdioTransport::class, fn ($app): StdioTransport => new StdioTransport($app, $input, fopen('stdio-test-output://', 'w')));

    $this->artisan('craft:mcp:serve', ['--user' => User::query()->where('admin', true)->firstOrFail()->username])->assertSuccessful();

    expect(StdioTestOutput::$saved['create'])->toBeTrue();
});

/** Records, as each response is written, whether the Archive site group has been saved yet. */
class StdioTestOutput
{
    /** @var array<string, bool> */
    public static array $saved = [];

    /** @var resource|null */
    public $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    private string $buffer = '';

    public function stream_write(string $data): int
    {
        $this->buffer .= $data;

        while (($end = strpos($this->buffer, PHP_EOL)) !== false) {
            $line = substr($this->buffer, 0, $end);
            $this->buffer = substr($this->buffer, $end + strlen(PHP_EOL));
            self::$saved[Json::decode($line)['id'] ?? ''] = DB::table('projectconfig')->where('path', 'like', 'siteGroups.%.name')->where('value', Json::encode('Archive'))->exists();
        }

        return strlen($data);
    }
}

it('sees sections added since an earlier message read element sources', function (): void {
    EntryElement::sources(ElementSources::CONTEXT_MODAL);
    $section = Section::factory()->create();

    $responses = ($this->serve)(User::query()->where('admin', true)->firstOrFail()->username, [[
        'jsonrpc' => '2.0',
        'id' => 'create',
        'method' => 'tools/call',
        'params' => ['name' => 'configuration_create', 'arguments' => ['type' => 'fields', 'attributes' => [
            'type' => EntriesField::class,
            'name' => 'Related',
            'handle' => 'related',
            'settings' => ['sources' => ["section:$section->uid"]],
        ]]],
    ]]);

    expect($responses['create']['result']['isError'])->toBeFalse()
        ->and($responses['create']['result']['structuredContent']['item']['settings']['sources'])->toBe(["section:$section->uid"]);
});
