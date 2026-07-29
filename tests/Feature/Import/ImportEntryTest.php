<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout as FieldLayoutConfig;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sites;

// Built via the fluent FieldLayoutConfig API directly (rather than the FieldLayoutFactory::withContentTab()
// helper) because that helper currently passes it raw arrays instead of FieldLayoutElement instances — a
// pre-existing, unrelated bug (see the plan notes) that breaks every test relying on it.
function makeTitleFieldLayout(): FieldLayout
{
    $config = FieldLayoutConfig::make(EntryElement::class);
    $config->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['required' => true])));

    return FieldLayout::factory()->create(['type' => EntryElement::class, 'config' => $config->getConfig()]);
}

beforeEach(function () {
    $this->import = app(Import::class);

    $this->typeA = EntryType::factory()
        ->withFieldLayout(makeTitleFieldLayout())
        ->create(['name' => 'Type A', 'handle' => 'typeA', 'hasTitleField' => true]);
    $this->typeB = EntryType::factory()
        ->withFieldLayout(makeTitleFieldLayout())
        ->create(['name' => 'Type B', 'handle' => 'typeB', 'hasTitleField' => true]);

    $this->section = Section::factory()
        ->withEntryTypes($this->typeA, $this->typeB)
        ->create(['type' => SectionType::Channel]);

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->fieldLayout(EntryTypes::getEntryTypeById($this->typeA->id)->getFieldLayout())
        ->transformer(null);
});

it('uses the entry type selected via the field layout provider, ignoring a typeId in the incoming data', function () {
    // typeId passed as an already-resolved int, to avoid the separate bug documented below.
    $this->import->importItem($this->importer, [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->typeB->id,
    ]);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getTypeId())->toBe($this->typeA->id);
});
