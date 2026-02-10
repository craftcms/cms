<?php

declare(strict_types=1);

use craft\behaviors\CustomFieldBehavior;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementRelations;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->relations = app(ElementRelations::class);

    actingAs(User::findOne());
});

it('is a singleton', function () {
    $instance1 = app(ElementRelations::class);
    $instance2 = app(ElementRelations::class);

    expect($instance1)->toBe($instance2);
});

describe('updateRelations', function () {
    beforeEach(function () {
        $this->field = Field::factory()->create([
            'handle' => 'relatedEntries',
            'type' => Entries::class,
        ]);

        $this->fieldLayout = FieldLayout::factory()->forField($this->field)->create();

        $this->section = Section::factory()->create([
            'handle' => 'testSection',
        ]);

        $this->entryType = EntryType::factory()->create([
            'fieldLayoutId' => $this->fieldLayout->id,
        ]);

        CustomFieldBehavior::$fieldHandles[$this->field->handle] = true;
        Fields::refreshFields();
    });

    it('creates relations for a new element', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntry = EntryModel::factory()->create();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', [$targetEntry->id]);

        Craft::$app->getElements()->saveElement($element);

        $relation = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->where('fieldId', $this->field->id)
            ->first();

        expect($relation)->not->toBeNull();
        expect($relation->targetId)->toBe($targetEntry->id);
        expect($relation->sortOrder)->toBe(1);
    });

    it('creates multiple relations with correct sort order', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntries = EntryModel::factory(3)->create();
        $targetIds = $targetEntries->pluck('id')->all();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', $targetIds);

        Craft::$app->getElements()->saveElement($element);

        $relations = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->where('fieldId', $this->field->id)
            ->orderBy('sortOrder')
            ->get();

        expect($relations)->toHaveCount(3);
        expect($relations[0]->targetId)->toBe($targetIds[0]);
        expect($relations[0]->sortOrder)->toBe(1);
        expect($relations[1]->targetId)->toBe($targetIds[1]);
        expect($relations[1]->sortOrder)->toBe(2);
        expect($relations[2]->targetId)->toBe($targetIds[2]);
        expect($relations[2]->sortOrder)->toBe(3);
    });

    it('updates existing relations when element is resaved', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntries = EntryModel::factory(2)->create();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', [$targetEntries[0]->id]);
        Craft::$app->getElements()->saveElement($element);

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', [$targetEntries[1]->id]);
        Craft::$app->getElements()->saveElement($element);

        $relations = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->where('fieldId', $this->field->id)
            ->get();

        expect($relations)->toHaveCount(1);
        expect($relations[0]->targetId)->toBe($targetEntries[1]->id);
    });

    it('removes relations when cleared', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntry = EntryModel::factory()->create();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', [$targetEntry->id]);
        Craft::$app->getElements()->saveElement($element);

        expect(DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count())->toBe(1);

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', []);
        Craft::$app->getElements()->saveElement($element);

        expect(DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count())->toBe(0);
    });

    it('reorders relations when sort order changes', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntries = EntryModel::factory(3)->create();
        $targetIds = $targetEntries->pluck('id')->all();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', $targetIds);
        Craft::$app->getElements()->saveElement($element);

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', array_reverse($targetIds));
        Craft::$app->getElements()->saveElement($element);

        $relations = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->where('fieldId', $this->field->id)
            ->orderBy('sortOrder')
            ->get();

        expect($relations)->toHaveCount(3);
        expect($relations[0]->targetId)->toBe($targetIds[2]);
        expect($relations[1]->targetId)->toBe($targetIds[1]);
        expect($relations[2]->targetId)->toBe($targetIds[0]);
    });

    it('does nothing for elements without field layouts', function () {
        $entry = EntryModel::factory()->create();
        $entry->element->update(['fieldLayoutId' => null]);

        $element = entryQuery()->id($entry->id)->firstOrFail();

        $initialCount = DB::table(Table::RELATIONS)->count();

        $this->relations->updateRelations($element, true);

        expect(DB::table(Table::RELATIONS)->count())->toBe($initialCount);
    });

    it('handles duplicate target ids by using first occurrence', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntry = EntryModel::factory()->create();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();
        $element->setFieldValue('relatedEntries', [$targetEntry->id, $targetEntry->id, $targetEntry->id]);
        Craft::$app->getElements()->saveElement($element);

        $relations = DB::table(Table::RELATIONS)
            ->where('sourceId', $element->id)
            ->where('fieldId', $this->field->id)
            ->get();

        expect($relations)->toHaveCount(1);
        expect($relations[0]->targetId)->toBe($targetEntry->id);
        expect($relations[0]->sortOrder)->toBe(1);
    });
});

describe('deleteSiteRelations', function () {
    beforeEach(function () {
        $this->field = Field::factory()->create([
            'handle' => 'siteRelatedEntries',
            'type' => Entries::class,
        ]);

        $this->fieldLayout = FieldLayout::factory()->forField($this->field)->create();

        $this->section = Section::factory()->create([
            'handle' => 'siteTestSection',
        ]);

        $this->entryType = EntryType::factory()->create([
            'fieldLayoutId' => $this->fieldLayout->id,
        ]);

        CustomFieldBehavior::$fieldHandles[$this->field->handle] = true;
        Fields::refreshFields();
    });

    it('deletes site-specific relations', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntry = EntryModel::factory()->create();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();

        DB::table(Table::RELATIONS)->insert([
            'fieldId' => $this->field->id,
            'sourceId' => $element->id,
            'sourceSiteId' => $element->siteId,
            'targetId' => $targetEntry->id,
            'sortOrder' => 1,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        expect(DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count())->toBe(1);

        $this->relations->deleteSiteRelations($element);

        expect(DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count())->toBe(0);
    });

    it('only deletes relations for the specific site', function () {
        $sourceEntry = EntryModel::factory()->create([
            'sectionId' => $this->section->id,
            'typeId' => $this->entryType->id,
        ]);
        $sourceEntry->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

        $targetEntry = EntryModel::factory()->create();

        $element = entryQuery()->id($sourceEntry->id)->firstOrFail();

        DB::table(Table::RELATIONS)->insert([
            'fieldId' => $this->field->id,
            'sourceId' => $element->id,
            'sourceSiteId' => $element->siteId,
            'targetId' => $targetEntry->id,
            'sortOrder' => 1,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        DB::table(Table::RELATIONS)->insert([
            'fieldId' => $this->field->id,
            'sourceId' => $element->id,
            'sourceSiteId' => null,
            'targetId' => $targetEntry->id,
            'sortOrder' => 1,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        expect(DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count())->toBe(2);

        $this->relations->deleteSiteRelations($element);

        $remainingRelations = DB::table(Table::RELATIONS)->where('sourceId', $element->id)->get();
        expect($remainingRelations)->toHaveCount(1);
        expect($remainingRelations[0]->sourceSiteId)->toBeNull();
    });

    it('does nothing for elements without field layouts', function () {
        $field = Field::factory()->create([
            'handle' => 'noLayoutField',
            'type' => Entries::class,
        ]);

        $entry = EntryModel::factory()->create();
        $entry->element->update(['fieldLayoutId' => null]);

        $targetEntry = EntryModel::factory()->create();

        $element = entryQuery()->id($entry->id)->firstOrFail();

        DB::table(Table::RELATIONS)->insert([
            'fieldId' => $field->id,
            'sourceId' => $element->id,
            'sourceSiteId' => $element->siteId,
            'targetId' => $targetEntry->id,
            'sortOrder' => 1,
            'dateCreated' => now(),
            'dateUpdated' => now(),
            'uid' => Str::uuid(),
        ]);

        $initialCount = DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count();

        $this->relations->deleteSiteRelations($element);

        expect(DB::table(Table::RELATIONS)->where('sourceId', $element->id)->count())->toBe($initialCount);
    });
});

describe('relationalFields', function () {
    it('returns empty array for elements without field layouts', function () {
        $entry = EntryModel::factory()->create();
        $entry->element->update(['fieldLayoutId' => null]);

        $element = entryQuery()->id($entry->id)->firstOrFail();

        $fields = $this->relations->relationalFields($element);

        expect($fields)->toBeEmpty();
    });

    it('returns empty array when no relational fields exist', function () {
        $fieldLayout = FieldLayout::factory()->create();

        $section = Section::factory()->create([
            'handle' => 'noRelationSection',
        ]);

        $entryType = EntryType::factory()->create([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $entry = EntryModel::factory()->create([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->element->update(['fieldLayoutId' => $fieldLayout->id]);

        $element = entryQuery()->id($entry->id)->firstOrFail();

        $fields = $this->relations->relationalFields($element);

        expect($fields)->toBeEmpty();
    });

    it('returns relational fields grouped by field id', function () {
        $field = Field::factory()->create([
            'handle' => 'groupedEntriesField',
            'type' => Entries::class,
        ]);

        $fieldLayout = FieldLayout::factory()->forField($field)->create();

        $section = Section::factory()->create([
            'handle' => 'groupedSection',
        ]);

        $entryType = EntryType::factory()->create([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        CustomFieldBehavior::$fieldHandles[$field->handle] = true;
        Fields::refreshFields();

        $entry = EntryModel::factory()->create([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
        ]);
        $entry->element->update(['fieldLayoutId' => $fieldLayout->id]);

        $element = entryQuery()->id($entry->id)->firstOrFail();

        $fields = $this->relations->relationalFields($element);

        expect($fields)->not->toBeEmpty();
        expect($fields)->toHaveKey($field->id);
        expect($fields[$field->id])->toBeArray();
        expect($fields[$field->id][0]->handle)->toBe('groupedEntriesField');
    });
});
