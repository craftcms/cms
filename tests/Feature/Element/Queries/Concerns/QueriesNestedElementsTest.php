<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
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

    expect(entryQuery()->primaryOwner(Craft::$app->getElements()->getElementById($entry->id))->count())->toBe(1);
    expect(entryQuery()->primaryOwnerId($entry->id)->count())->toBe(1);

    expect(entryQuery()->ownerId($entry->id)->count())->toBe(1);
    expect(entryQuery()->owner(Craft::$app->getElements()->getElementById($entry->id))->count())->toBe(1);

    Craft::$app->getElements()->startCollectingCacheInfo();

    entryQuery()->fieldId($field->id)->count();
    entryQuery()->ownerId($entry->id)->count();

    /** @var TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.CraftCms\Cms\Entry\Elements\Entry::class.'::field:'.$field->id);
    expect($dependency->tags)->toContain('element::'.$entry->id);
});
