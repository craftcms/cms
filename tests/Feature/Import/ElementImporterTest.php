<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Import\AssetImporter;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
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

it('resolves fieldLayout() with an arbitrary string to a new unsaved layout for that type', function () {
    // `Fields::getLayoutByType()` defaults to creating an in-memory (unsaved) layout for
    // any type string that doesn't already have one — a string can never be "not found"
    // via this path, only a numeric ID can throw.
    $importer = EntryImporter::create()->fieldLayout('not-a-real-uid-and-not-a-class-string');

    expect($importer->fieldLayout)->toBe('not-a-real-uid-and-not-a-class-string');
});

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

it('has no registered importer for element types that never exist standalone', function () {
    $import = app(Import::class);

    expect($import->getElementImporterTypeFor(Address::class))->toBeNull()
        ->and($import->getElementImporterTypeFor(ContentBlock::class))->toBeNull();
});
