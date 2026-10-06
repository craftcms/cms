<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Mcp\Capabilities\Entries;
use CraftCms\Cms\Mcp\Public\ElementQuery;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Mcp\Exception\ToolCallException;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    $field = Field::factory()->create([
        'name' => 'Summary',
        'handle' => 'summary',
        'type' => PlainText::class,
    ]);
    $fieldLayout = FieldLayout::factory()->forField($field, required: true)->create();
    $entryType = EntryType::factory()->withFieldLayout($fieldLayout)->create();
    $this->section = Section::factory()->withEntryTypes($entryType)->create([
        'handle' => 'articles',
    ]);

    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();
});

it('reads custom fields on demand and expands only one level of nested content', function (): void {
    $leafText = Field::factory()->create(['handle' => 'leafText', 'type' => PlainText::class]);
    $leafType = EntryType::factory()->withField($leafText)->create(['handle' => 'leaf', 'hasTitleField' => false, 'titleFormat' => '{id}']);
    $blockText = Field::factory()->create(['handle' => 'blockText', 'type' => PlainText::class]);
    $innerBlocks = Field::factory()->create([
        'handle' => 'innerBlocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$leafType->id]],
    ]);
    $blockType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $blockText->uid]),
            new CustomField(config: ['fieldUid' => $innerBlocks->uid]),
        ]))
        ->create(['handle' => 'block', 'hasTitleField' => false, 'titleFormat' => '{id}']);
    $entry = EntryModel::factory()
        ->withField('teaser', PlainText::class, value: 'A short teaser')
        ->withField('blocks', Matrix::class, ['entryTypes' => [$blockType->id]], value: [
            'new1' => [
                'type' => 'block',
                'fields' => [
                    'blockText' => 'First level content',
                    'innerBlocks' => [
                        'new1' => ['type' => 'leaf', 'fields' => ['leafText' => 'Deeper content']],
                    ],
                ],
            ],
        ])
        ->createElementWithFields()->element;

    $block = $entry->getFieldValue('blocks')->one();
    expect($block->getFieldValue('innerBlocks')->one()->getFieldValue('leafText'))->toBe('Deeper content');

    $entries = app(Entries::class);
    $listed = $entries->list(['id' => $entry->id])->structuredContent['entries'][0];

    expect($listed)->not->toHaveKeys(['teaser', 'blocks']);

    $detail = $entries->get(id: $entry->id)['entry'];
    expect($detail['teaser'])->toBe('A short teaser')
        ->and($detail)->not->toHaveKey('blocks')
        ->and($entries->get(id: $entry->id, fields: [])['entry'])->not->toHaveKeys(['teaser', 'blocks']);

    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    $selected = McpRequest::send($this, 'tools/call', [
        'name' => 'entries.list',
        'arguments' => ['criteria' => ['id' => $entry->id], 'fields' => ['blocks']],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent.entries.0');

    expect($selected)->not->toHaveKey('teaser')
        ->and($selected['blocks'])->toHaveCount(1)
        ->and($selected['blocks'][0]['blockText'])->toBe('First level content')
        ->and($selected['blocks'][0])->not->toHaveKey('innerBlocks');

    app()->instance(Settings::class, new Settings([
        'publicSiteHandles' => [Sites::getPrimarySite()->handle],
        'publicSectionHandles' => [$entry->getSection()->handle],
    ]));
    $public = app(ElementQuery::class);

    expect($public->query('entry', ['id' => $entry->id])['elements'][0])->not->toHaveKeys(['teaser', 'blocks'])
        ->and($public->query('entry', ['id' => $entry->id], fields: ['blocks'])['elements'][0]['blocks'])->toBe([]);

    app(Settings::class)->publicNestedEntryFieldHandles = ['blocks'];
    $publicBlock = $public->query('entry', ['id' => $entry->id], fields: ['blocks'])['elements'][0]['blocks'][0];

    expect($publicBlock['blockText'])->toBe('First level content')
        ->and($publicBlock)->not->toHaveKey('innerBlocks');
});

it('manages entries through MCP', function () {
    $entries = app(Entries::class);
    $fieldSchema = $entries->fieldSchema(sectionId: $this->section->id)['schema'];
    $created = $entries->create([
        'sectionId' => $this->section->id,
        'title' => 'First title',
        'enabled' => true,
    ], ['summary' => 'Original summary'])['entry'];
    $listed = $entries->list([
        'sectionId' => $this->section->id,
        'status' => null,
    ])->structuredContent;
    $updated = $entries->update(
        id: $created['id'],
        attributes: ['title' => 'Updated title'],
        fields: ['summary' => 'Updated summary'],
    )['entry'];
    $fetched = $entries->get(uid: $created['uid'])['entry'];
    $deleted = $entries->delete(id: $created['id'], hardDelete: true);

    expect($fieldSchema)->toMatchArray([
        'type' => 'object',
        'properties' => [
            'summary' => [
                'type' => 'string',
                'title' => 'Summary',
            ],
        ],
        'required' => ['summary'],
        'additionalProperties' => false,
    ])
        ->and($created['authorId'])->toBe(User::query()->firstOrFail()->id)
        ->and($created['summary'])->toBe('Original summary')
        ->and($listed['entries'])->toHaveCount(1)
        ->and($listed['entries'][0])->toMatchArray([
            'id' => $created['id'],
            'sectionId' => $this->section->id,
            'title' => 'First title',
            'authorId' => User::query()->firstOrFail()->id,
        ])
        ->and($updated['title'])->toBe('Updated title')
        ->and($updated['summary'])->toBe('Updated summary')
        ->and($fetched)->toMatchArray([
            'id' => $created['id'],
            'uid' => $created['uid'],
            'title' => 'Updated title',
            'summary' => 'Updated summary',
        ])
        ->and($deleted)->toBe(['deleted' => true])
        ->and(Entry::find()->id($created['id'])->status(null)->one())->toBeNull();
});

it('requires paginated queries for large nested collections', function (): void {
    $blockType = EntryType::factory()->create(['handle' => 'limitBlock', 'hasTitleField' => false, 'titleFormat' => '{id}']);
    $blocks = [];

    foreach (range(1, 101) as $index) {
        $blocks["new$index"] = ['type' => 'limitBlock'];
    }

    $result = EntryModel::factory()
        ->withField('largeBlocks', Matrix::class, ['entryTypes' => [$blockType->id]], value: $blocks)
        ->createElementWithFields();
    $entries = app(Entries::class);

    expect($entries->get(id: $result->element->id)['entry'])->not->toHaveKey('largeBlocks')
        ->and(fn () => $entries->get(id: $result->element->id, fields: ['largeBlocks']))
        ->toThrow(ToolCallException::class, 'Too many nested elements to expand');

    $criteria = ['ownerId' => $result->element->id, 'fieldId' => $result->fields['largeBlocks']->id, 'limit' => 100];

    $firstPage = $entries->list($criteria)->structuredContent['entries'];
    $lastPage = $entries->list([...$criteria, 'offset' => 100])->structuredContent['entries'];

    expect($firstPage)->toHaveCount(100)
        ->and($lastPage)->toHaveCount(1)
        ->and(array_intersect(array_column($firstPage, 'id'), array_column($lastPage, 'id')))->toBe([]);

    $entries->delete(id: $lastPage[0]['id'], hardDelete: true);

    expect($entries->get(id: $result->element->id, fields: ['largeBlocks'])['entry']['largeBlocks'])->toHaveCount(100);
});
