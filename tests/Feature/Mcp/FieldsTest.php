<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Laravel\Passport\Passport;

it('manages fields through MCP while preserving omitted attributes and merging settings', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $call = fn (string $operation, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => "configuration_$operation",
        'arguments' => ['type' => 'fields', ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');

    $created = $call('create', ['attributes' => [
        'type' => PlainText::class,
        'name' => 'Summary',
        'handle' => 'summary',
        'instructions' => 'Write a summary.',
        'searchable' => false,
        'settings' => ['placeholder' => 'Start writing'],
    ]])['item'];
    $listed = $call('list', []);
    $updated = $call('update', [
        'identifier' => ['id' => $created['id']],
        'attributes' => [
            'name' => 'Introduction',
            'instructions' => null,
            'settings' => ['charLimit' => 120],
        ],
    ])['item'];
    $fetched = $call('get', ['identifier' => ['handle' => 'summary']])['item'];
    $resource = McpRequest::send($this, 'resources/read', ['uri' => 'craft://fields/summary'])
        ->assertOk()->json('result.contents.0.text');
    $deleted = $call('delete', ['identifier' => ['uid' => $created['uid']]]);

    expect($created)->toMatchArray([
        'name' => 'Summary',
        'handle' => 'summary',
        'type' => PlainText::class,
    ])
        ->and($created['settings']['placeholder'])->toBe('Start writing')
        ->and($listed['count'])->toBe(1)
        ->and($listed['items'][0]['id'])->toBe($created['id'])
        ->and($updated)->toMatchArray([
            'id' => $created['id'],
            'name' => 'Introduction',
            'searchable' => false,
            'instructions' => null,
        ])
        ->and($updated['settings']['placeholder'])->toBe('Start writing')
        ->and($updated['settings']['charLimit'])->toBe(120)
        ->and($fetched['name'])->toBe('Introduction')
        ->and(json_decode($resource, true)['field']['id'])->toBe($created['id'])
        ->and($deleted)->toBe(['type' => 'fields', 'deleted' => true])
        ->and(Fields::getFieldById($created['id']))->toBeNull();
});

it('redacts only sensitive settings in MCP responses', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    app(ProjectConfig::class)->set('plugins.mcpTest.settings', ['apiKey' => 'sk_live_123', 'region' => 'us-east-1']);
    $call = fn (string $tool, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => $arguments,
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');

    $settings = [
        'sources' => '*',
        'defaultPlacement' => Entries::DEFAULT_PLACEMENT_BEGINNING,
        'viewMode' => 'cards',
        'selectionLabel' => 'Choose an entry',
    ];
    $call('configuration_create', ['type' => 'fields', 'attributes' => [
        'type' => Entries::class,
        'name' => 'Related',
        'handle' => 'related',
        'settings' => $settings,
    ]]);

    expect($call('configuration_get', ['type' => 'fields', 'identifier' => ['handle' => 'related']])['item']['settings'])
        ->toMatchArray($settings)
        ->and($call('project_config_get', ['path' => 'plugins.mcpTest.settings'])['value'])
        ->toBe(['apiKey' => '•••••••••••', 'region' => 'us-east-1']);
});

it('accepts the settings it returned for a Matrix field when updating it through MCP', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $entryType = EntryType::factory()->create();
    $call = fn (string $operation, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => "configuration_$operation",
        'arguments' => ['type' => 'fields', ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.item');

    $created = $call('create', ['attributes' => [
        'type' => Matrix::class,
        'name' => 'Blocks',
        'handle' => 'blocks',
        'settings' => ['entryTypes' => [$entryType->uid]],
    ]]);
    $updated = $call('update', [
        'identifier' => ['id' => $created['id']],
        'attributes' => ['settings' => [...$created['settings'], 'maxEntries' => 5]],
    ]);

    expect($updated['settings']['maxEntries'])->toBe(5)
        ->and(Fields::getFieldById($created['id'])->getEntryTypes()[0]->uid)->toBe($entryType->uid);
});
