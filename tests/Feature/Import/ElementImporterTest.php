<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site as SiteModel;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\ImportHelper;

it('returns every field layout provider when the element type has more than one field layout', function () {
    $fieldLayoutA = FieldLayout::factory()->create(['type' => EntryElement::class]);
    $fieldLayoutB = FieldLayout::factory()->create(['type' => EntryElement::class]);
    $entryTypeA = EntryType::factory()->withFieldLayout($fieldLayoutA)->create(['name' => 'Provider A', 'handle' => 'providerA']);
    $entryTypeB = EntryType::factory()->withFieldLayout($fieldLayoutB)->create(['name' => 'Provider B', 'handle' => 'providerB']);
    Section::factory()->withEntryTypes($entryTypeA, $entryTypeB)->create(['type' => SectionType::Channel]);

    $providers = ImportHelper::getAvailableFieldLayoutProviders(EntryElement::class);
    $values = array_column($providers, 'value');

    expect($values)->toContain($fieldLayoutA->uid)
        ->and($values)->toContain($fieldLayoutB->uid)
        ->and($providers[0])->toBe(['label' => 'Please select', 'value' => '']);
});

it('falls back to the singular field layout when the element type has none via the plural method', function () {
    $providers = ImportHelper::getAvailableFieldLayoutProviders(Address::class);

    expect($providers)->toHaveCount(1)
        ->and($providers[0]['label'])->toBe(Address::displayName())
        ->and($providers[0]['value'])->toBe((new Address)->getFieldLayout()->type);
});

it('resolves site() from a numeric ID', function () {
    $site = SiteModel::factory()->create();

    $importer = ElementImporter::create()->site(Sites::getSiteById($site->id)->id);

    expect($importer->site->id)->toBe($site->id);
});

it('resolves site() from a UID', function () {
    $site = SiteModel::factory()->create();

    $importer = ElementImporter::create()->site(Sites::getSiteById($site->id)->uid);

    expect($importer->site->id)->toBe($site->id);
});

it('resolves site(null) to the primary site', function () {
    $importer = ElementImporter::create()->site(null);

    expect($importer->site->id)->toBe(Sites::getPrimarySite()->id);
});

it('throws when site() is given a nonexistent numeric ID', function () {
    ElementImporter::create()->site(999999);
})->throws(InvalidArgumentException::class, 'No site found with ID: 999999');

it('throws when site() is given a nonexistent handle or UID', function () {
    ElementImporter::create()->site('nonexistent-handle');
})->throws(InvalidArgumentException::class, 'No site found with handle or UID: "nonexistent-handle".');

it('resolves fieldLayout() from a numeric ID', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $importer = ElementImporter::create()->fieldLayout($fieldLayout->id);

    expect($importer->fieldLayout)->toBe($fieldLayout->uid);
});

it('resolves fieldLayout() from a UID', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $importer = ElementImporter::create()->fieldLayout($fieldLayout->uid);

    expect($importer->fieldLayout)->toBe($fieldLayout->uid);
});

it('resolves fieldLayout() from an existing type without creating a new layout', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => Address::class]);
    Fields::refreshFields();

    $importer = ElementImporter::create()->fieldLayout(Address::class);

    expect($importer->fieldLayout)->toBe($fieldLayout->uid);
});

it('throws when fieldLayout() is given a nonexistent numeric ID', function () {
    ElementImporter::create()->fieldLayout(999999);
})->throws(InvalidArgumentException::class, 'No field layout found with ID: 999999');

it('resolves fieldLayout() with an arbitrary string to a new unsaved layout for that type', function () {
    // `Fields::getLayoutByType()` defaults to creating an in-memory (unsaved) layout for
    // any type string that doesn't already have one — a string can never be "not found"
    // via this path, only a numeric ID can throw.
    $importer = ElementImporter::create()->fieldLayout('not-a-real-uid-and-not-a-class-string');

    expect($importer->fieldLayout)->toBe('not-a-real-uid-and-not-a-class-string');
});
