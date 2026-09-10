<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
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
        'handle' => 'nestedBlocksOne',
        'type' => ContentBlock::class,
    ]);

    $field2 = Field::factory()->create([
        'handle' => 'nestedBlocksTwo',
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

test('field(false) only returns entries with no field', function () {
    $field = Field::factory()->create([
        'type' => ContentBlock::class,
    ]);

    Fields::refreshFields();

    $topLevelEntry = Entry::factory()->create();
    $owner = Entry::factory()->create();
    $nested = Entry::factory()->create([
        'primaryOwnerId' => $owner->id,
        'fieldId' => $field->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)
        ->insert([
            'elementId' => $nested->id,
            'ownerId' => $owner->id,
            'sortOrder' => 1,
        ]);

    expect(entryQuery()->field(false)->ids())->toEqualCanonicalizing([$topLevelEntry->id, $owner->id]);
});

test('owner filters preserve shared nested entries and the selected owners sort order', function () {
    $field = Field::factory()->create(['type' => ContentBlock::class]);
    Fields::refreshFields();

    $primaryOwner = Entry::factory()->create();
    $secondaryOwner = Entry::factory()->create();
    $first = Entry::factory()->create(['primaryOwnerId' => $primaryOwner->id, 'fieldId' => $field->id]);
    $second = Entry::factory()->create(['primaryOwnerId' => $primaryOwner->id, 'fieldId' => $field->id]);

    DB::table(Table::ELEMENTS_OWNERS)->insert([
        ['elementId' => $first->id, 'ownerId' => $primaryOwner->id, 'sortOrder' => 1],
        ['elementId' => $second->id, 'ownerId' => $primaryOwner->id, 'sortOrder' => 2],
        ['elementId' => $first->id, 'ownerId' => $secondaryOwner->id, 'sortOrder' => 2],
        ['elementId' => $second->id, 'ownerId' => $secondaryOwner->id, 'sortOrder' => 1],
    ]);

    $query = entryQuery()->fieldId($field->id)->ownerId($secondaryOwner->id);

    expect($query->ids())->toBe([$second->id, $first->id]);
    expect($query->count())->toBe(2);
    expect(collect($query->all())->pluck('ownerId')->all())->toBe([$secondaryOwner->id, $secondaryOwner->id]);
    expect((clone $query)->ownerId($primaryOwner->id)->ids())->toBe([$first->id, $second->id]);
    expect(entryQuery()->ownerId([$primaryOwner->id, $secondaryOwner->id])->count())->toBe(4);
    expect(entryQuery()->ownerId([$primaryOwner->id, $secondaryOwner->id])->ids())->toEqualCanonicalizing([$first->id, $first->id, $second->id, $second->id]);
    expect(entryQuery()->ownerId([])->ids())->toEqualCanonicalizing([$primaryOwner->id, $secondaryOwner->id, $first->id, $second->id]);
    expect(entryQuery()->ownerId(-1)->count())->toBe(0);
    expect(entryQuery()->ownerId(false)->ids())->toEqualCanonicalizing([$primaryOwner->id, $secondaryOwner->id, $first->id, $second->id]);

    $secondSite = Site::factory()->create();
    $first->element->siteSettings->first()->update(['siteId' => $secondSite->id]);
    Sites::refreshSites();

    expect(entryQuery()->ownerId($secondaryOwner->id)->ids())->toBe([$second->id]);
    expect(entryQuery()->ownerId($secondaryOwner->id)->siteId($secondSite->id)->ids())->toBe([$first->id]);
});
