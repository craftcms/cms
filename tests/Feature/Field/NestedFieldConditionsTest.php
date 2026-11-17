<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Conditions\TypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $nestedField = Field::factory()->create(['handle' => 'nestedText', 'type' => PlainText::class]);

    $this->blockType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([new CustomField(config: ['fieldUid' => $nestedField->uid])]))
        ->create(['handle' => 'block', 'hasTitleField' => false]);
    $this->otherBlockType = EntryType::factory()->create(['handle' => 'otherBlock']);

    $matrix = Field::factory()->create([
        'handle' => 'blocks',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$this->blockType->id]],
    ]);
    $contentBlock = Field::factory()->create([
        'handle' => 'contentBlock',
        'type' => ContentBlock::class,
        'settings' => [
            'fieldLayouts' => [
                Str::uuid()->toString() => [
                    'tabs' => [[
                        'uid' => Str::uuid()->toString(),
                        'name' => 'Content',
                        'elements' => [[
                            'uid' => Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $nestedField->uid,
                        ]],
                    ]],
                ],
            ],
        ],
    ]);

    $entryModel = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new CustomField(config: ['fieldUid' => $matrix->uid]),
            new CustomField(config: ['fieldUid' => $contentBlock->uid]),
        ]))
        ->create();
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();

    $this->entry = entryQuery()->id($entryModel->id)->status(null)->one();

    actingAs(UserModel::findOrFail(UserModel::factory()->createElement(['admin' => false])->id));
});

function nestedLayoutElement(Entry $entry, string $fieldHandle, EntryType $blockType): CustomField
{
    $layout = $fieldHandle === 'blocks'
        ? EntryTypes::getEntryTypeById($blockType->id)->getFieldLayout()
        : $entry->getFieldLayout()->getFieldByHandle('contentBlock')->getFieldLayout();

    return $layout->getFieldByHandle('nestedText')->layoutElement;
}

function submitNestedValue(Entry $entry, string $fieldHandle): mixed
{
    if ($fieldHandle === 'blocks') {
        $entry->setFieldValueFromRequest('blocks', [
            'sortOrder' => ['new:1'],
            'entries' => ['new:1' => ['type' => 'block', 'fields' => ['nestedText' => 'Nested']]],
        ]);

        return $entry->getFieldValue('blocks')->all()[0]->getFieldValue('nestedText');
    }

    $entry->setFieldValueFromRequest('contentBlock', ['fields' => ['nestedText' => 'Nested']]);

    return $entry->getFieldValue('contentBlock')->getFieldValue('nestedText');
}

function adminOnlyCondition(): mixed
{
    $condition = User::createCondition();
    $condition->addConditionRule(new AdminConditionRule(['value' => true]));

    return $condition;
}

it('sets editable nested field values', function (string $fieldHandle): void {
    expect(submitNestedValue($this->entry, $fieldHandle))->toBe('Nested');
})->with(['blocks', 'contentBlock']);

it('ignores nested field values locked by edit conditions', function (string $fieldHandle): void {
    nestedLayoutElement($this->entry, $fieldHandle, $this->blockType)->setEditCondition(adminOnlyCondition());

    expect(submitNestedValue($this->entry, $fieldHandle))->toBeNull();
})->with(['blocks', 'contentBlock']);

it('ignores nested field values locked by element edit conditions', function (): void {
    $condition = Entry::createCondition();
    $condition->addConditionRule(new TypeConditionRule(['values' => [$this->otherBlockType->uid]]));
    nestedLayoutElement($this->entry, 'blocks', $this->blockType)->setElementEditCondition($condition);

    expect(submitNestedValue($this->entry, 'blocks'))->toBeNull();
});

it('lets GraphQL token requests set any nested field', function (string $fieldHandle): void {
    nestedLayoutElement($this->entry, $fieldHandle, $this->blockType)->setEditCondition(adminOnlyCondition());
    app(Gql::class)->setActiveSchema(new GqlSchema(['name' => 'Token']));

    expect(submitNestedValue($this->entry, $fieldHandle))->toBe('Nested');
})->with(['blocks', 'contentBlock']);
