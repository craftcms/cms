<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element as BaseElement;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Mcp\Capabilities\Drafts;
use CraftCms\Cms\Mcp\Capabilities\Entries;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\User\Elements\User as UserElement;
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

it('manages entry drafts through MCP', function () {
    $entry = app(Entries::class)->create([
        'sectionId' => $this->section->id,
        'title' => 'Canonical title',
        'enabled' => true,
    ])['entry'];
    $drafts = app(Drafts::class);

    $created = $drafts->create(
        type: 'entries',
        id: $entry['id'],
        name: 'Revised article',
        notes: 'Editorial changes',
    )['draft'];

    $draft = Entry::find()->drafts()->id($created['id'])->status(null)->one();
    $draft->title = 'Draft title';
    expect(app(Elements::class)->saveElement($draft))->toBeTrue();

    $listed = $drafts->list(type: 'entries', id: $entry['id']);
    $applied = $drafts->apply(type: 'entries', uid: $created['uid'])['element'];
    $discardedDraft = $drafts->create(type: 'entries', id: $entry['id'])['draft'];
    $deleted = $drafts->delete(type: 'entries', id: $discardedDraft['id']);

    expect($listed)->toMatchArray([
        'count' => 1,
        'limit' => 100,
        'offset' => 0,
    ])
        ->and($listed['drafts'][0])->toMatchArray([
            'id' => $created['id'],
            'canonicalId' => $entry['id'],
            'draftName' => 'Revised article',
            'draftNotes' => 'Editorial changes',
        ])
        ->and($applied)->toMatchArray([
            'id' => $entry['id'],
            'title' => 'Draft title',
        ])
        ->and($deleted)->toBe(['deleted' => true])
        ->and(Entry::find()->drafts()->id([$created['id'], $discardedDraft['id']])->status(null)->exists())->toBeFalse();
});

it('supports registered plugin element types', function () {
    app(ElementTypes::class)->register(TestMcpDraftElement::class);

    $element = new TestMcpDraftElement;
    expect(app(Elements::class)->saveElement($element))->toBeTrue();

    $drafts = app(Drafts::class);
    $created = $drafts->create(type: 'test-mcp-draft-element', id: $element->id)['draft'];
    $listed = $drafts->list(type: 'test-mcp-draft-element', id: $element->id);

    expect($created)->toMatchArray([
        'type' => TestMcpDraftElement::class,
        'canonicalId' => $element->id,
    ])->and($listed['drafts'])->toHaveCount(1)
        ->and($listed['drafts'][0])->toMatchArray([
            'type' => TestMcpDraftElement::class,
            'id' => $created['id'],
            'canonicalId' => $element->id,
        ]);
});

class TestMcpDraftElement extends BaseElement
{
    #[Override]
    public static function displayName(): string
    {
        return 'Test MCP draft element';
    }

    #[Override]
    public static function refHandle(): string
    {
        return 'test-mcp-draft-element';
    }

    #[Override]
    public function canView(UserElement $user): bool
    {
        return true;
    }

    #[Override]
    public function canCreateDrafts(UserElement $user): bool
    {
        return true;
    }
}
