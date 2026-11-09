<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Enums\MatchMode;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->import = app(Import::class);

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

    $this->importer = new EntryImporter()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);
});

it('uses the entry type selected via the field layout provider, ignoring a typeId in the incoming data', function () {
    $importerWithUid = clone ($this->importer, [
        'uid' => 'i-have-a-uid',
    ]);
    $importerWithUid->section($this->section->uid)
        ->entryType($this->typeA->uid);
    $this->import->importItem($importerWithUid, [
        'title' => 'imported entry',
    ]);

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getTypeId())->toBe($this->typeA->id);
});

dataset('requiredValueScenarios', [
    'section requires an author' => [[
        'section' => fn ($test) => $test->sectionWithAuthors,
        'entryType' => fn ($test) => $test->typeA,
        'importer' => fn ($test) => $test->importer,
        'mappedData' => function ($test) {
            $test->requiredValueAuthor = User::factory()->create();

            return ['authorIds' => [$test->requiredValueAuthor->id]];
        },
        'assertMapped' => fn ($test, EntryElement $entry) => expect($entry->getAuthorIds())->toBe([$test->requiredValueAuthor->id]),
    ]],
    'entry type has a required Plain Text field' => [[
        'section' => fn ($test) => Section::factory()->withEntryTypes($test->typeC)->create(['minAuthors' => 0]),
        'entryType' => fn ($test) => $test->typeC,
        'importer' => fn ($test) => $test->importer,
        'mappedData' => fn ($test) => ['myRequiredText' => 'some value'],
        'assertMapped' => fn ($test, EntryElement $entry) => expect($entry->getFieldValue('myRequiredText'))->toBe('some value'),
    ]],
]);

describe('required-value validation, skipping enabled entries and allowing disabled ones', function () {
    it('fails and skips an entry imported enabled without the required value mapped', function (array $scenario) {
        $importer = ($scenario['importer'])($this);
        $importer->section(($scenario['section'])($this)->uid)->entryType(($scenario['entryType'])($this)->uid);

        expect(fn () => $this->import->importItem($importer, [
            'title' => 'imported entry',
        ]))->toThrow(InvalidElementException::class);

        $entry = EntryElement::find()->title('imported entry')->one();

        expect($entry)->toBeNull();
    })->with('requiredValueScenarios');

    it('succeeds importing a disabled entry without the required value mapped', function (array $scenario) {
        $importer = ($scenario['importer'])($this);
        $section = ($scenario['section'])($this);
        $importer->section($section->uid)->entryType(($scenario['entryType'])($this)->uid);

        $this->import->importItem($importer, [
            'title' => 'imported entry',
            'enabled' => false,
        ]);

        $entry = EntryElement::find()->title('imported entry')->section($section->handle)->status(null)->one();

        expect($entry)->not->toBeNull();
    })->with('requiredValueScenarios');

    it('succeeds importing an enabled entry with the required value mapped', function (array $scenario) {
        $importer = ($scenario['importer'])($this);
        $importer->section(($scenario['section'])($this)->uid)->entryType(($scenario['entryType'])($this)->uid);

        $this->import->importItem($importer, [
            'title' => 'imported entry',
            ...($scenario['mappedData'])($this),
        ]);

        $entry = EntryElement::find()->title('imported entry')->status(null)->one();

        expect($entry)->not->toBeNull();
        ($scenario['assertMapped'])($this, $entry);
    })->with('requiredValueScenarios');
});

it('doesn’t import an unmapped field, even when it’s match criteria and the incoming key matches its handle', function () {
    ImportFixtures::plainTextField('optionalText', 'Optional Text');
    Fields::refreshFields();

    $entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make('optionalText')],
        ['name' => 'With Optional Text', 'handle' => 'withOptionalText'],
    );
    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

    $importer = $this->importer
        ->section($section->uid)
        ->entryType($entryType->uid)
        ->map(['title' => 'title', 'optionalText' => null])
        ->matchCriteria(['optionalText' => 1]);

    ImportFixtures::importWithConfigCriteria($this->import, $importer, [
        'title' => 'imported entry',
        'optionalText' => 'should not be imported',
    ]);

    $entry = EntryElement::find()->title('imported entry')->status(null)->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getFieldValue('optionalText'))->toBeNull();
});

it('imports values rendered from mapping templates', function () {
    ImportFixtures::plainTextField('optionalText', 'Optional Text');
    Fields::refreshFields();

    $entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make('optionalText')],
        ['name' => 'With Optional Text', 'handle' => 'withOptionalText'],
    );
    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

    $importer = $this->importer
        ->section($section->uid)
        ->entryType($entryType->uid)
        ->map(['title' => ImportHelper::TEMPLATE_RULE, 'optionalText' => ImportHelper::TEMPLATE_RULE])
        ->templates(['title' => '{first} {last}', 'optionalText' => "Born {{ object['Birth Year'] }}"]);

    $this->import->importItem($importer, ['first' => 'Ada', 'last' => 'Lovelace', 'Birth Year' => 1815]);

    $entry = EntryElement::find()->title('Ada Lovelace')->status(null)->one();

    expect($entry)->not->toBeNull()
        ->and($entry->getFieldValue('optionalText'))->toBe('Born 1815');
});

it('matches on the value a mapping template renders', function () {
    $importer = $this->importer
        ->section($this->section->uid)
        ->entryType($this->typeA->uid)
        ->map(['title' => 'title', 'slug' => ImportHelper::TEMPLATE_RULE])
        ->templates(['slug' => '{title|kebab}'])
        ->matchCriteria(['slug' => 1]);

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ['title' => 'Getting Started']);
    ImportFixtures::importWithConfigCriteria($this->import, $importer, ['title' => 'Getting  started']);

    $entries = EntryElement::find()->sectionId($this->section->id)->status(null)->all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->slug)->toBe('getting-started')
        ->and($entries[0]->title)->toBe('Getting  started');
});

describe('an empty match value', function () {
    beforeEach(function () {
        $this->import->importItem(
            $this->importer->section($this->section->uid)->entryType($this->typeA->uid)->map(['title' => 'title', 'slug' => 'slug']),
            ['title' => 'Untouched', 'slug' => 'untouched'],
        );

        $this->templateImporter = fn (MatchMode $mode) => $this->importer
            ->map(['title' => 'title', 'slug' => ImportHelper::TEMPLATE_RULE])
            ->templates(['slug' => '{missing}'])
            ->matchCriteria(['slug' => 1])
            ->matchMode($mode);

        $this->titles = fn () => EntryElement::find()->sectionId($this->section->id)->status(null)->orderBy('id')->collect()->pluck('title')->all();
    });

    // with no condition to match on, it would match whichever element came first
    it('matches nothing, creating a new element', function () {
        ImportFixtures::importWithConfigCriteria($this->import, ($this->templateImporter)(MatchMode::CreateAndUpdate), ['title' => 'New one']);

        expect(($this->titles)())->toBe(['Untouched', 'New one']);
    });

    it('imports nothing when only updating', function () {
        ImportFixtures::importWithConfigCriteria($this->import, ($this->templateImporter)(MatchMode::UpdateOnly), ['title' => 'New one']);

        expect(($this->titles)())->toBe(['Untouched']);
    });

    it('counts whitespace as empty', function () {
        $importer = $this->importer->map(['title' => 'title', 'slug' => 'slug'])->matchCriteria(['slug' => 1]);

        ImportFixtures::importWithConfigCriteria($this->import, $importer, ['title' => 'New one', 'slug' => '  ']);

        expect(($this->titles)())->toBe(['Untouched', 'New one']);
    });

    it('is left out of match criteria that come with the data', function () {
        $this->import->importItem(
            $this->importer->map(['title' => 'title', 'slug' => 'slug']),
            ['title' => 'New one', 'slug' => '', 'matchCriteria' => ['slug' => 'slug']],
        );

        expect(($this->titles)())->toBe(['Untouched', 'New one']);
    });
});
