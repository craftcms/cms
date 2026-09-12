<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->import = app(Import::class);

    // these entry types get their own sections below, so ImportFixtures::seedEntry() (which always
    // creates a section and a seed entry) isn't a fit — only the entry type part is shared
    $this->typeA = ImportFixtures::entryTypeWithTitle(attrs: ['name' => 'Type A', 'handle' => 'typeA']);
    $this->typeB = ImportFixtures::entryTypeWithTitle(attrs: ['name' => 'Type B', 'handle' => 'typeB']);

    $this->section = Section::factory()->withEntryTypes($this->typeA, $this->typeB)->create(['minAuthors' => 0]);

    $this->sectionWithAuthors = Section::factory()->withEntryTypes($this->typeA)->create();

    $requiredTextField = Field::factory()->create(['name' => 'My Required Text', 'handle' => 'myRequiredText', 'type' => PlainText::class]);

    Fields::refreshFields();

    $this->typeC = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($requiredTextField->handle)->required()],
        ['name' => 'With Required Text', 'handle' => 'withRequiredText'],
    );

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
