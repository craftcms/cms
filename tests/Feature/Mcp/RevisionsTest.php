<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Mcp\Capabilities\Entries;
use CraftCms\Cms\Mcp\Capabilities\Revisions;
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
    $this->section = Section::factory()->withEntryTypes($entryType)->create([
        'enableVersioning' => true,
    ]);

    EntryTypes::refreshEntryTypes();
});

it('inspects and applies entry revisions through MCP', function () {
    $entries = app(Entries::class);
    $created = $entries->create([
        'sectionId' => $this->section->id,
        'title' => 'Original title',
        'enabled' => true,
    ])['entry'];
    $entries->update(id: $created['id'], attributes: ['title' => 'Current title']);

    $revisions = app(Revisions::class);
    $listed = $revisions->list(type: 'entries', id: $created['id']);
    $original = collect($listed['revisions'])->firstWhere('title', 'Original title');

    expect($original)->not->toBeNull();

    $fetched = $revisions->get(type: 'entries', uid: $original['uid'])['revision'];
    $applied = $revisions->apply(type: 'entries', id: $original['id'])['element'];
    $canonical = Entry::find()->id($created['id'])->status(null)->one();

    expect($listed)->toMatchArray([
        'limit' => 100,
        'offset' => 0,
    ])
        ->and($listed['count'])->toBeGreaterThanOrEqual(1)
        ->and($fetched)->toMatchArray([
            'id' => $original['id'],
            'canonicalId' => $created['id'],
            'title' => 'Original title',
        ])
        ->and($applied)->toMatchArray([
            'id' => $created['id'],
            'title' => 'Original title',
        ])
        ->and($canonical->title)->toBe('Original title');
});
