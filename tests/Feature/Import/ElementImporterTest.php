<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;

it('returns every field layout provider when the element type has more than one field layout', function () {
    $fieldLayoutA = FieldLayout::factory()->create(['type' => EntryElement::class]);
    $fieldLayoutB = FieldLayout::factory()->create(['type' => EntryElement::class]);
    $entryTypeA = EntryType::factory()->withFieldLayout($fieldLayoutA)->create(['name' => 'Provider A', 'handle' => 'providerA']);
    $entryTypeB = EntryType::factory()->withFieldLayout($fieldLayoutB)->create(['name' => 'Provider B', 'handle' => 'providerB']);
    Section::factory()->withEntryTypes($entryTypeA, $entryTypeB)->create(['type' => SectionType::Channel]);

    $importer = ElementImporter::create()->className(EntryElement::class);

    $providers = $importer->getAvailableFieldLayoutProviders();
    $values = array_column($providers, 'value');

    expect($values)->toContain($fieldLayoutA->uid)
        ->and($values)->toContain($fieldLayoutB->uid)
        ->and($providers[0])->toBe(['label' => 'Please select', 'value' => '']);
});

it('falls back to the singular field layout when the element type has none via the plural method', function () {
    $importer = ElementImporter::create()->className(Address::class);

    $providers = $importer->getAvailableFieldLayoutProviders();

    expect($providers)->toHaveCount(1)
        ->and($providers[0]['label'])->toBe(Address::displayName())
        ->and($providers[0]['value'])->toBe((new Address)->getFieldLayout()->type);
});
