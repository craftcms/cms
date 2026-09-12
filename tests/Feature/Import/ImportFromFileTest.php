<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    // BaseImporter::resolvedFilePath() resolves everything against @root, which points at the
    // Testbench skeleton during tests - point it at the package so the fixtures are reachable
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    $blockEntryType = ImportFixtures::blockEntryType('blockEt', [$plainTextField], 'Block ET');
    $nestedMatrixField = ImportFixtures::matrixField('myNestedMatrix', [$blockEntryType], 'My Nested Matrix');
    $outerEntryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($nestedMatrixField->handle)],
        ['name' => 'Outer ET', 'handle' => 'outerEt'],
    );
    $matrixField = ImportFixtures::matrixField('myMatrix', [$blockEntryType, $outerEntryType], 'My Matrix');

    // the fixture files reference these handles, so they have to be pinned rather than generated
    $this->entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle), CustomField::make($matrixField->handle)],
        ['name' => 'Fixture Type', 'handle' => 'fixtureType'],
    );
    $this->section = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['handle' => 'fixtureSection', 'minAuthors' => 0]);

    // the entry types above were created after the last refresh inside matrixField(), so the
    // caches have to be primed again before the Import can resolve them by handle
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->fixturePath = fn (string $name) => dirname(__DIR__, 2).'/Fixtures/Import/'.$name;

    $this->importerFor = fn (string $name) => ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null)
        ->file('tests/Fixtures/Import/'.$name);
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

// reading and formatting

it('reads and formats a JSON file into rows', function () {
    $data = $this->import->getFormattedData(($this->fixturePath)('entries-plain-text.json'));

    expect($data)->toHaveCount(3)
        ->and($data[0]['title'])->toBe('first file entry')
        ->and($data[0]['plainText'])->toBe('text from the file')
        ->and($data[1]['slug'])->toBe('second-file-entry-slug');
});

it('reads and formats a CSV file into rows keyed by the heading row', function () {
    $data = $this->import->getFormattedData(($this->fixturePath)('entries.csv'));

    expect($data)->toHaveCount(2)
        ->and($data[0])->toBe([
            'sectionId' => 'fixtureSection',
            'typeId' => 'fixtureType',
            'title' => 'first csv entry',
            'plainText' => 'csv text one',
        ]);
});

it('reads and formats an XML file into rows, unwrapping the root element', function () {
    $data = $this->import->getFormattedData(($this->fixturePath)('entries.xml'));

    expect($data)->toHaveCount(2)
        ->and($data[0]['title'])->toBe('first xml entry')
        ->and($data[1]['plainText'])->toBe('xml text two');
});

it('throws when the file cannot be read', function () {
    expect(fn () => $this->import->getRawData(($this->fixturePath)('does-not-exist.json')))
        ->toThrow(Exception::class);
});

it('throws for an empty file', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('empty.json')))
        ->toThrow(Exception::class, 'Unable to parse data.');
});

it('throws the data type’s own error for malformed JSON', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('broken.json')))
        ->toThrow(Exception::class);
});

it('reports an unsupported file extension as an unsupported data type', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('unsupported.txt')))
        ->toThrow(Exception::class, 'Unsupported data type: txt');
});

it('reports an unsupported file extension when reading headings too', function () {
    expect(fn () => $this->import->getDataHeadings(($this->fixturePath)('unsupported.txt')))
        ->toThrow(Exception::class, 'Unsupported data type: txt');
});

// A data file is expected to hold a list of rows; Import::Import() foreaches whatever comes back,
// so a single top-level object hands importItem() each of its values instead of one row.
it('fails with a type error when a JSON file holds a single object instead of a list of rows', function () {
    expect(fn () => $this->import->import(($this->importerFor)('single-object.json')))
        ->toThrow(TypeError::class);
});

it('returns the source headings with a "Please select" option prepended', function () {
    $headings = $this->import->getDataHeadings(($this->fixturePath)('entries-plain-text.json'));

    expect($headings[0])->toBe(['label' => 'Please select', 'value' => ''])
        ->and(array_column($headings, 'value'))->toContain('title', 'plainText', 'slug');
});

// importing whole files

it('imports every row of a JSON file', function () {
    $this->import->import(($this->importerFor)('entries-plain-text.json'));

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))->toBe('text from the file')
        ->and(EntryElement::find()->title('second file entry')->one()->slug)->toBe('second-file-entry-slug');
});

it('imports a file’s nested matrix blocks', function () {
    $this->import->import(($this->importerFor)('entries-matrix.json'));

    $entry = EntryElement::find()->title('file entry with matrix')->one();
    $blocks = $entry->getFieldValue('myMatrix')->all();

    expect($blocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->title, $blocks))->toBe(['file block 1', 'file block 2'])
        ->and($blocks[0]->getFieldValue('plainText'))->toBe('block one');
});

it('imports a file’s matrix-in-matrix blocks', function () {
    $this->import->import(($this->importerFor)('entries-matrix-in-matrix.json'));

    $entry = EntryElement::find()->title('file entry with matrix in matrix')->one();
    $outerBlock = $entry->getFieldValue('myMatrix')->one();
    $innerBlocks = $outerBlock->getFieldValue('myNestedMatrix')->all();

    expect($outerBlock->title)->toBe('file outer block')
        ->and($innerBlocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->getFieldValue('plainText'), $innerBlocks))->toBe(['inner one', 'inner two']);
});

// Import::Import() resolves the importer's own matchCriteria before handing each row to
// importItem() - the wiring that importItem()-level tests can't cover.
it('matches rows and nested blocks on re-import using the importer config’s match criteria alone', function () {
    $importer = ($this->importerFor)('entries-matrix.json')->matchCriteria([
        'title' => 'title',
        'myMatrix' => [
            'blockEt' => ['title' => 'title'],
        ],
    ]);

    $this->import->import($importer);

    $entry = EntryElement::find()->title('file entry with matrix')->one();
    $entryId = $entry->id;
    $blockIds = $entry->getFieldValue('myMatrix')->ids();

    $this->import->import($importer);

    $entry = EntryElement::find()->title('file entry with matrix')->one();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(1)
        ->and($entry->id)->toBe($entryId)
        ->and($entry->getFieldValue('myMatrix')->ids())->toBe($blockIds);
});

it('creates duplicates on re-import when no match criteria is configured', function () {
    $importer = ($this->importerFor)('entries-plain-text.json');

    $this->import->import($importer);
    $this->import->import($importer);

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(6);
});
