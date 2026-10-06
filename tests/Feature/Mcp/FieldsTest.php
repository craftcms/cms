<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Laravel\Passport\Passport;

it('manages fields through MCP while preserving omitted attributes and merging settings', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    $call = fn (string $operation, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => "configuration.$operation",
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
