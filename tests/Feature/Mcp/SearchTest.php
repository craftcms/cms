<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Mcp\Capabilities\Entries;
use CraftCms\Cms\Mcp\Capabilities\Search;
use CraftCms\Cms\Mcp\Capabilities\Users;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    $entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($entryType)->create();

    EntryTypes::refreshEntryTypes();
});

it('searches across element types through MCP', function () {
    $query = 'cross type needle';
    $user = User::query()->firstOrFail();

    $entry = app(Entries::class)->create([
        'sectionId' => $this->section->id,
        'title' => $query,
        'enabled' => true,
    ])['entry'];
    app(Users::class)->update(id: $user->id, attributes: ['firstName' => $query]);

    $search = app(Search::class)->query($query, ['entries', 'users']);
    $results = collect($search['results'])->keyBy('elementType');

    expect($search)->toMatchArray([
        'query' => $query,
        'types' => ['entry', 'user'],
        'count' => 2,
        'counts' => ['entry' => 1, 'user' => 1],
    ])
        ->and($results['entry']['element']['id'])->toBe($entry['id'])
        ->and($results['user']['element']['id'])->toBe($user->id);
});
