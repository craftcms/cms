<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element as BaseElement;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Mcp\Capabilities\Drafts;
use CraftCms\Cms\Mcp\Capabilities\Elements as ElementCapabilities;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Mcp\Exception\ToolCallException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    $field = Field::factory()->create(['handle' => 'summary', 'type' => PlainText::class]);
    $entryType = EntryType::factory()->withFieldLayout(FieldLayout::factory()->forField($field, required: true))->create();
    $this->section = Section::factory()->withEntryTypes($entryType)->create();

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();
});

it('manages entry drafts through MCP', function () {
    $entry = app(ElementCapabilities::class)->create('entries', [
        'sectionId' => $this->section->id,
        'title' => 'Canonical title',
        'enabled' => true,
    ], ['summary' => 'Canonical summary'])['element'];
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

it('creates new entries as saved unpublished drafts only when asked', function (): void {
    $elements = app(ElementCapabilities::class);
    $attributes = ['sectionId' => $this->section->id, 'title' => 'Proposed article', 'enabled' => true];

    $result = $elements->create('entries', $attributes,
        asUnpublishedDraft: true,
        draftName: 'Agent draft',
        draftNotes: 'Needs a summary',
    );
    $draft = Entry::find()->drafts()->savedDraftsOnly()->id($result['element']['id'])->status(null)->one();

    expect($result['savedAsDraft'])->toBeTrue()
        ->and($result)->not->toHaveKey('nextToolCall')
        ->and($result['instructions'])->toContain("unpublished draft (ID {$draft->id})")
        ->and($draft->getIsUnpublishedDraft())->toBeTrue()
        ->and($draft->draftName)->toBe('Agent draft')
        ->and($draft->draftNotes)->toBe('Needs a summary')
        ->and($draft->draftCreatorId)->toBe(User::query()->firstOrFail()->id)
        ->and(Entry::find()->sectionId($this->section->id)->status(null)->exists())->toBeFalse()
        ->and(fn () => $elements->create('entries', $attributes))->toThrow(ToolCallException::class, 'field is required.')
        ->and(fn () => $elements->create('entries', $attributes, ['summary' => 'Live summary'], draftName: 'Agent draft'))
        ->toThrow(ToolCallException::class, 'draftName and draftNotes require asUnpublishedDraft.');

    $live = $elements->create('entries', $attributes, ['summary' => 'Live summary']);

    expect(array_keys($live))->toBe(['element'])
        ->and(Entry::find()->id($live['element']['id'])->one()->getIsCanonical())->toBeTrue();
});

it('lists unpublished drafts only when asked', function (): void {
    $elements = app(ElementCapabilities::class);
    $drafts = app(Drafts::class);
    $attributes = ['sectionId' => $this->section->id, 'title' => 'Proposed article'];

    $unpublished = $elements->create('entries', $attributes, asUnpublishedDraft: true)['element'];
    $entry = $elements->create('entries', $attributes, ['summary' => 'Live summary'])['element'];
    $draftOfEntry = $drafts->create(type: 'entries', id: $entry['id'])['draft'];

    expect(collect($drafts->list(type: 'entries')['drafts'])->pluck('id')->all())->toBe([$draftOfEntry['id']])
        ->and(collect($drafts->list(type: 'entries', unpublished: true)['drafts'])->pluck('id')->all())->toBe([$unpublished['id']])
        ->and(fn () => $drafts->list(type: 'entries', id: $unpublished['id']))
        ->toThrow(ToolCallException::class, "Element {$unpublished['id']} is an unpublished draft and has no canonical element.")
        ->and(fn () => $drafts->list(type: 'entries', id: $unpublished['id'], unpublished: true))
        ->toThrow(ToolCallException::class, 'omit id and uid when unpublished is true.')
        ->and(fn () => $drafts->list(type: 'entries', id: 999999))
        ->toThrow(ToolCallException::class, 'Canonical element not found.');
});

it('updates entry drafts by their element ID without changing the canonical entry', function (): void {
    $elements = app(ElementCapabilities::class);
    $entry = $elements->create('entries', [
        'sectionId' => $this->section->id,
        'title' => 'Canonical title',
        'enabled' => true,
    ], ['summary' => 'Canonical summary'])['element'];
    $draft = app(Drafts::class)->create(type: 'entries', id: $entry['id'])['draft'];

    $schema = $elements->fieldSchema('entries', id: $draft['id'])['schema'];
    $updated = $elements->update('entries',
        id: $draft['id'],
        attributes: ['title' => 'Draft title'],
        fields: ['summary' => 'Draft summary'],
    );

    expect($schema['properties'])->toHaveKey('summary')
        ->and($updated['savedAsDraft'])->toBeTrue()
        ->and($updated['element'])->toMatchArray(['id' => $draft['id'], 'title' => 'Draft title', 'summary' => 'Draft summary'])
        ->and($elements->get('entries', uid: $draft['uid'])['element'])->toMatchArray([
            'id' => $draft['id'],
            'canonicalId' => $entry['id'],
            'title' => 'Draft title',
            'summary' => 'Draft summary',
        ])
        ->and($elements->get('entries', id: $entry['id'])['element'])->toMatchArray([
            'title' => 'Canonical title',
            'summary' => 'Canonical summary',
        ])
        ->and(Entry::find()->drafts()->draftOf($entry['id'])->status(null)->count())->toBe(1);
});

it('requires entry permissions to create and edit drafts', function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    $entry = app(ElementCapabilities::class)->create('entries', [
        'sectionId' => $this->section->id,
        'title' => 'Canonical title',
        'enabled' => true,
    ], ['summary' => 'Canonical summary'])['element'];
    $draft = app(Drafts::class)->create(type: 'entries', id: $entry['id'])['draft'];
    $viewPermissions = [
        'accessCp', 'useCraftMcp',
        "viewEntries:{$this->section->uid}", "viewPeerEntries:{$this->section->uid}", "viewPeerEntryDrafts:{$this->section->uid}",
    ];
    $viewer = User::factory()->withPermissions($viewPermissions)->create(['admin' => false]);
    $editor = User::factory()->withPermissions([
        ...$viewPermissions,
        "createEntries:{$this->section->uid}", "savePeerEntryDrafts:{$this->section->uid}",
    ])->create(['admin' => false]);
    $call = function (User $actor, string $tool, array $arguments): array {
        Passport::actingAs($actor, ['mcp:admin'], 'craft-mcp');

        return McpRequest::send($this, 'tools/call', ['name' => $tool, 'arguments' => ['type' => 'entries', ...$arguments]])
            ->assertOk()
            ->json('result');
    };
    $createDraft = ['asUnpublishedDraft' => true, 'attributes' => ['sectionId' => $this->section->id, 'title' => 'Proposed article']];
    $updateDraft = ['id' => $draft['id'], 'attributes' => ['title' => 'Draft title']];

    expect($call($viewer, 'elements.create', $createDraft)['content'][0]['text'])->toBe('You are not authorized to save this entry.')
        ->and($call($viewer, 'elements.update', $updateDraft)['content'][0]['text'])->toBe('You are not authorized to save this entry.')
        ->and(Entry::find()->drafts()->sectionId($this->section->id)->status(null)->ids())->toBe([$draft['id']])
        ->and(Entry::find()->drafts()->id($draft['id'])->status(null)->one()->title)->toBe('Canonical title')
        ->and($call($editor, 'elements.create', $createDraft))->toMatchArray(['isError' => false])
        ->and($call($editor, 'elements.update', $updateDraft))->toMatchArray(['isError' => false])
        ->and(Entry::find()->id($entry['id'])->one()->title)->toBe('Canonical title');
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
