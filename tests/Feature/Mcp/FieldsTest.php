<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Mcp\Capabilities\Fields;
use CraftCms\Cms\Support\Facades\Fields as FieldFacade;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('manages fields through MCP', function () {
    $fields = app(Fields::class);
    $created = $fields->create(
        type: PlainText::class,
        name: 'Summary',
        handle: 'summary',
        settings: ['placeholder' => 'Start writing'],
    )['field'];
    $listed = $fields->list();
    $request = new CallToolRequest('fields.update', [
        'id' => $created['id'],
        'name' => 'Introduction',
        'settings' => ['charLimit' => 120],
    ]);
    $updated = $fields->update(
        context: new RequestContext(new Session(new InMemorySessionStore), $request),
        id: $created['id'],
        name: 'Introduction',
        settings: ['charLimit' => 120],
    )['field'];
    $fetched = $fields->get(handle: 'summary')['field'];
    $deleted = $fields->delete(uid: $created['uid']);

    expect($created)->toMatchArray([
        'name' => 'Summary',
        'handle' => 'summary',
        'type' => PlainText::class,
    ])
        ->and($created['settings']['placeholder'])->toBe('Start writing')
        ->and($listed['count'])->toBe(1)
        ->and($updated)->toMatchArray([
            'id' => $created['id'],
            'name' => 'Introduction',
        ])
        ->and($updated['settings']['placeholder'])->toBe('Start writing')
        ->and($updated['settings']['charLimit'])->toBe(120)
        ->and($fetched['name'])->toBe('Introduction')
        ->and($deleted)->toBe(['deleted' => true])
        ->and(FieldFacade::getFieldById($created['id']))->toBeNull();
});
