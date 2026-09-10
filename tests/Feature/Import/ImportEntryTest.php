<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout as FieldLayoutConfig;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;

function makeTitleFieldLayout(): FieldLayout
{
    $config = FieldLayoutConfig::make(EntryElement::class);
    $config->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['required' => true])));

    return FieldLayout::factory()->create(['type' => EntryElement::class, 'config' => $config->getConfig()]);
}

function makeRequiredPlainTextFieldLayout(): FieldLayout
{
    $field = Field::factory()->create(['name' => 'My Required Text', 'handle' => 'myRequiredText', 'type' => PlainText::class]);

    Fields::refreshFields();

    $config = FieldLayoutConfig::make(EntryElement::class);
    $config->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
        new EntryTitleField(['required' => true]),
        CustomField::make($field->handle)->required(),
    ));

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

    $this->section = Section::factory()->withEntryTypes($this->typeA, $this->typeB)->create(['minAuthors' => 0]);

    $this->sectionWithAuthors = Section::factory()->withEntryTypes($this->typeA)->create();

    $this->typeC = EntryType::factory()
        ->withFieldLayout(makeRequiredPlainTextFieldLayout())
        ->create(['name' => 'With Required Text', 'handle' => 'withRequiredText', 'hasTitleField' => true]);

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->fieldLayout(EntryTypes::getEntryTypeById($this->typeA->id)->getFieldLayout())
        ->transformer(null);
});

it('uses the entry type selected via the field layout provider, ignoring a typeId in the incoming data', function () {
    // typeId passed as an already-resolved int, to avoid the separate bug documented below.
    $importerWithUid = clone ($this->importer, [
        'uid' => 'i-have-a-uid',
    ]);
    $this->import->importItem($importerWithUid, [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->typeB->id,
    ]);

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getTypeId())->toBe($this->typeA->id);
});

dataset('requiredValueScenarios', [
    'section requires an author' => [[
        'section' => fn ($test) => $test->sectionWithAuthors,
        'importer' => fn ($test) => $test->importer,
        'mappedData' => function ($test) {
            $test->requiredValueAuthor = User::factory()->create();

            return ['authorIds' => [$test->requiredValueAuthor->id]];
        },
        'assertMapped' => fn ($test, EntryElement $entry) => expect($entry->getAuthorIds())->toBe([$test->requiredValueAuthor->id]),
    ]],
    'entry type has a required Plain Text field' => [[
        'section' => fn ($test) => Section::factory()->withEntryTypes($test->typeC)->create(['minAuthors' => 0]),
        'importer' => fn ($test) => $test->importer->fieldLayout(EntryTypes::getEntryTypeById($test->typeC->id)->getFieldLayout()),
        'mappedData' => fn ($test) => ['myRequiredText' => 'some value'],
        'assertMapped' => fn ($test, EntryElement $entry) => expect($entry->getFieldValue('myRequiredText'))->toBe('some value'),
    ]],
]);

describe('required-value validation, skipping enabled entries and allowing disabled ones', function () {
    it('fails and skips an entry imported enabled without the required value mapped', function (array $scenario) {
        $section = ($scenario['section'])($this);
        $importer = ($scenario['importer'])($this);

        $this->import->importItem($importer, [
            'title' => 'imported entry',
            'sectionId' => $section->handle,
        ]);

        $entry = EntryElement::find()->title('imported entry')->one();

        expect($entry)->toBeNull();
    })->with('requiredValueScenarios');

    it('succeeds importing a disabled entry without the required value mapped', function (array $scenario) {
        $section = ($scenario['section'])($this);
        $importer = ($scenario['importer'])($this);

        $this->import->importItem($importer, [
            'title' => 'imported entry',
            'sectionId' => $section->handle,
            'enabled' => false,
        ]);

        $entry = EntryElement::find()->title('imported entry')->status(null)->one();

        expect($entry)->not->toBeNull();
    })->with('requiredValueScenarios');

    it('succeeds importing an enabled entry with the required value mapped', function (array $scenario) {
        $section = ($scenario['section'])($this);
        $importer = ($scenario['importer'])($this);

        $this->import->importItem($importer, [
            'title' => 'imported entry',
            'sectionId' => $section->handle,
            ...($scenario['mappedData'])($this),
        ]);

        $entry = EntryElement::find()->title('imported entry')->status(null)->one();

        expect($entry)->not->toBeNull();
        ($scenario['assertMapped'])($this, $entry);
    })->with('requiredValueScenarios');
});
