<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use Illuminate\Support\Facades\DB;

test('nested element query', function () {
    $field = Field::factory()->create([
        'type' => ContentBlock::class,
    ]);

    Fields::refreshFields();

    $entry = Entry::factory()->create();
    $nested = Entry::factory()->create([
        'primaryOwnerId' => $entry->id,
        'fieldId' => $field->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)
        ->insert([
            'elementId' => $nested->id,
            'ownerId' => $entry->id,
            'sortOrder' => 1,
        ]);

    expect(entryQuery()->count())->toBe(2);

    expect(entryQuery()->fieldId($field->id)->count())->toBe(1);
    expect(entryQuery()->fieldId($field->id)->first()->ownerId)->not()->toBeNull();
    expect(entryQuery()->fieldId($field->id)->first()->sortOrder)->not()->toBeNull();

    expect(entryQuery()->field($field->handle)->count())->toBe(1);
    expect(entryQuery()->field(Fields::getFieldById($field->id))->count())->toBe(1);

    expect(entryQuery()->primaryOwner(Elements::getElementById($entry->id))->count())->toBe(1);
    expect(entryQuery()->primaryOwnerId($entry->id)->count())->toBe(1);

    expect(entryQuery()->ownerId($entry->id)->count())->toBe(1);
    expect(entryQuery()->owner(Elements::getElementById($entry->id))->count())->toBe(1);

    ElementCaches::startCollectingCacheInfo();

    entryQuery()->fieldId($field->id)->count();
    entryQuery()->ownerId($entry->id)->count();

    /** @var TagDependency $dependency */
    $dependency = ElementCaches::stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.CraftCms\Cms\Entry\Elements\Entry::class.'::field:'.$field->id);
    expect($dependency->tags)->toContain('element::'.$entry->id);
});

test('nested element query supports array filters', function () {
    $field1 = Field::factory()->create([
        'type' => ContentBlock::class,
    ]);

    $field2 = Field::factory()->create([
        'type' => ContentBlock::class,
    ]);

    Fields::refreshFields();

    $owner1 = Entry::factory()->create();
    $owner2 = Entry::factory()->create();

    $nested1 = Entry::factory()->create([
        'primaryOwnerId' => $owner1->id,
        'fieldId' => $field1->id,
    ]);

    $nested2 = Entry::factory()->create([
        'primaryOwnerId' => $owner2->id,
        'fieldId' => $field2->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)
        ->insert([
            [
                'elementId' => $nested1->id,
                'ownerId' => $owner1->id,
                'sortOrder' => 1,
            ],
            [
                'elementId' => $nested2->id,
                'ownerId' => $owner2->id,
                'sortOrder' => 1,
            ],
        ]);

    $nestedIds = [$nested1->id, $nested2->id];

    expect(entryQuery()->fieldId([$field1->id, $field2->id])->ids())->toEqualCanonicalizing($nestedIds);
    expect(entryQuery()->field([$field1->handle, $field2->handle])->ids())->toEqualCanonicalizing($nestedIds);
    expect(entryQuery()->primaryOwnerId([$owner1->id, $owner2->id])->ids())->toEqualCanonicalizing($nestedIds);
    expect(entryQuery()->ownerId([$owner1->id, $owner2->id])->ids())->toEqualCanonicalizing($nestedIds);
});
