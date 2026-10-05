<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Mcp\Capabilities\Entries;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;

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
    ]);
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
        ])
        ->and($deleted)->toBe(['deleted' => true])
        ->and(Entry::find()->id($created['id'])->status(null)->one())->toBeNull();
});
