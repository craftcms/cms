<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Import\AssetImporter;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Import\UserImporter;

it('resolves site() from a numeric ID', function () {
    $site = SiteModel::factory()->create();

    $importer = EntryImporter::create()->site(Sites::getSiteById($site->id)->id);

    expect($importer->site->id)->toBe($site->id);
});

it('resolves site() from a UID', function () {
    $site = SiteModel::factory()->create();

    $importer = EntryImporter::create()->site(Sites::getSiteById($site->id)->uid);

    expect($importer->site->id)->toBe($site->id);
});

it('resolves site(null) to the primary site', function () {
    $importer = EntryImporter::create()->site(null);

    expect($importer->site->id)->toBe(Sites::getPrimarySite()->id);
});

it('throws when site() is given a nonexistent numeric ID', function () {
    EntryImporter::create()->site(999999);
})->throws(InvalidArgumentException::class, 'No site found with ID: 999999');

it('throws when site() is given a nonexistent handle or UID', function () {
    EntryImporter::create()->site('nonexistent-handle');
})->throws(InvalidArgumentException::class, 'No site found with handle or UID: "nonexistent-handle".');

it('resolves fieldLayout() from a numeric ID', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $importer = EntryImporter::create()->fieldLayout($fieldLayout->id);

    expect($importer->fieldLayout)->toBe($fieldLayout->uid);
});

it('resolves fieldLayout() from a UID', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $importer = EntryImporter::create()->fieldLayout($fieldLayout->uid);

    expect($importer->fieldLayout)->toBe($fieldLayout->uid);
});

it('resolves fieldLayout() from an existing type without creating a new layout', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => Address::class]);
    Fields::refreshFields();

    $importer = EntryImporter::create()->fieldLayout(Address::class);

    expect($importer->fieldLayout)->toBe($fieldLayout->uid);
});

it('throws when fieldLayout() is given a nonexistent numeric ID', function () {
    EntryImporter::create()->fieldLayout(999999);
})->throws(InvalidArgumentException::class, 'No field layout found with ID: 999999');

it('resolves fieldLayout() with an element type that has no saved layout to that type', function () {
    $importer = EntryImporter::create()->fieldLayout(Address::class);

    expect($importer->fieldLayout)->toBe(Address::class);
});

it('throws when fieldLayout() is given a string that is neither a layout UID nor an element type', function () {
    EntryImporter::create()->fieldLayout('not-a-real-uid-and-not-a-class-string');
})->throws(InvalidArgumentException::class, 'No field layout found with UID or Type of: "not-a-real-uid-and-not-a-class-string".');

it('registers a concrete ElementImporter subclass per core importable element type', function (string $importerClass, string $elementClass) {
    expect(is_subclass_of($importerClass, ElementImporter::class))->toBeTrue()
        ->and($importerClass::targetClass())->toBe($elementClass)
        ->and($importerClass::displayName())->toBeString()->not->toBe('');

    $importer = $importerClass::create();

    expect($importer::targetClass())->toBe($elementClass);
})->with([
    'Entry' => [EntryImporter::class, EntryElement::class],
    'Asset' => [AssetImporter::class, AssetElement::class],
    'User' => [UserImporter::class, UserElement::class],
]);

it('lists the concrete element importer subclasses, not a bare ElementImporter, in the registry', function () {
    $types = app(Import::class)->getAllImporterTypes();

    expect($types)->not->toContain(ElementImporter::class)
        ->and($types)->toContain(EntryImporter::class)
        ->and($types)->toContain(AssetImporter::class)
        ->and($types)->toContain(UserImporter::class);
});

it('offers enabled but not parentId on the mapping screen for non-structured element types', function (string $importerClass) {
    $handles = collect($importerClass::create()->getDestinationCols())
        ->whereInstanceOf(MappingColumn::class)
        ->pluck('handle');

    expect($handles)->toContain('enabled')
        ->and($handles)->not->toContain('parentId');
})->with([
    'Asset' => [AssetImporter::class],
    'User' => [UserImporter::class],
]);

it('offers user groups on the mapping screen only on Craft Pro', function (Edition $edition, bool $offered) {
    Edition::set($edition);

    $handles = collect(UserImporter::create()->getDestinationCols())->pluck('handle');

    expect($handles->contains('groups'))->toBe($offered);
})->with([
    'Solo' => [Edition::Solo, false],
    'Pro' => [Edition::Pro, true],
]);

it('offers the affiliated site on the mapping screen only on multi-site installs', function () {
    $handles = collect(UserImporter::create()->getDestinationCols())->pluck('handle');

    expect($handles)->not->toContain('affiliatedSiteId');

    SiteModel::factory()->create();
    Sites::refreshSites();

    $handles = collect(UserImporter::create()->getDestinationCols())->pluck('handle');

    expect($handles)->toContain('affiliatedSiteId');
});

it('imports the affiliated site only on multi-site installs', function (bool $isMultiSite) {
    $site = $isMultiSite ? Sites::getSiteById(SiteModel::factory()->create()->id) : Sites::getPrimarySite();
    Sites::refreshSites();

    app(Import::class)->importItem(UserImporter::create()->site(Sites::getPrimarySite()->handle)->transformer(null), [
        'username' => 'importeduser',
        'email' => 'imported@example.com',
        'affiliatedSiteId' => $site->id,
    ]);

    $user = UserElement::find()->username('importeduser')->one();

    expect($user->affiliatedSiteId)->toBe($isMultiSite ? $site->id : null);
})->with([
    'single site' => [false],
    'multi-site' => [true],
]);
