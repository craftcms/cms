<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use CraftCms\DependencyAwareCache\Dependency\TagDependency;

use function Pest\Laravel\actingAs;

test('editable/savable returns 0 when having no access', function (string $method) {
    Edition::set(Edition::Pro);

    actingAs(User::find()->one());

    EntryModel::factory()->create();

    Sections::refreshSections();

    expect(entryQuery()->$method()->count())->toBe(1);

    actingAs(CraftCms\Cms\User\Models\User::factory()->createElement());

    // Access to nothing
    expect(entryQuery()->$method()->count())->toBe(0);
})->with([
    'editable',
    'savable',
]);

test('savable', function () {
    actingAs(User::find()->one());

    EntryModel::factory()->create();

    expect(entryQuery()->savable()->count())->toBe(1);
});

test('status', function () {
    EntryModel::factory()->create();
    EntryModel::factory()->pending()->create();
    EntryModel::factory()->expired()->create();

    expect(entryQuery()->count())->toBe(1);
    expect(entryQuery()->status(Entry::STATUS_PENDING)->count())->toBe(1);
    expect(entryQuery()->status(Entry::STATUS_EXPIRED)->count())->toBe(1);
});

test('it adds the entry type as a cache tag', function () {
    Craft::$app->getElements()->startCollectingCacheInfo();

    $entry = EntryModel::factory()->create();

    entryQuery()->typeId($entry->typeId)->all();

    /** @var TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.Entry::class.'::entryType:'.$entry->typeId);
});

test('it adds the section id as a cache tag', function () {
    Craft::$app->getElements()->startCollectingCacheInfo();

    $entry = EntryModel::factory()->create();

    entryQuery()->sectionId($entry->sectionId)->all();

    /** @var TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->toContain('element::'.Entry::class.'::section:'.$entry->sectionId);
});

test('it only adds the entry type id as a cache tag whyen both section and type are added', function () {
    Craft::$app->getElements()->startCollectingCacheInfo();

    $entry = EntryModel::factory()->create();

    entryQuery()->typeId($entry->typeId)->sectionId($entry->sectionId)->all();

    /** @var TagDependency $dependency */
    $dependency = Craft::$app->getElements()->stopCollectingCacheInfo()[0];

    expect($dependency->tags)->not()->toContain('element::'.Entry::class.'::section:'.$entry->sectionId);
    expect($dependency->tags)->toContain('element::'.Entry::class.'::entryType:'.$entry->typeId);
});
