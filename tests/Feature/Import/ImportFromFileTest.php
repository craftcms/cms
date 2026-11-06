<?php

declare(strict_types=1);

use CraftCms\Cms\Import\Data\SourceColumn;
use CraftCms\Cms\Import\DataTypes\Csv;
use CraftCms\Cms\Import\DataTypes\DataTypes;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Tests\Support\Import\TestDataType;
use CraftCms\Cms\Tests\Support\Import\TestImporter;
use Override;

beforeEach(function () {
    $this->import = app(Import::class);

    $this->fixturePath = fn (string $name) => dirname(__DIR__, 2).'/Fixtures/Import/'.$name;
});

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
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('unsupported.md')))
        ->toThrow(Exception::class, 'Unsupported data type: md');
});

it('reports an unsupported file extension when reading headings too', function () {
    expect(fn () => $this->import->getDataHeadings(($this->fixturePath)('unsupported.md')))
        ->toThrow(Exception::class, 'Unsupported data type: md');
});

it('reads .txt and .tsv files as CSV, splitting .tsv rows on tabs', function () {
    expect($this->import->getFormattedData(($this->fixturePath)('entries.txt')))
        ->toBe([['title' => 'first txt entry', 'plainText' => 'txt text one']])
        ->and($this->import->getFormattedData(($this->fixturePath)('entries.tsv')))
        ->toBe([['title' => 'first tsv entry', 'plainText' => 'tsv text, with a comma']]);
});

it('reads a CSV file with the step’s delimiter', function () {
    $importer = new TestImporter()->dataTypeSettings(['delimiter' => 'comma']);

    expect($this->import->getFormattedData(($this->fixturePath)('entries.tsv'), $importer))
        ->toBe([["title\tplainText" => "first tsv entry\ttsv text", '' => ' with a comma']]);
});

it('reads a file as the only data type claiming its extension that its contents match', function () {
    app(DataTypes::class)->register(TestDataType::class);

    expect($this->import->getFormattedData(($this->fixturePath)('entries.txt')))
        ->toBe([['title' => 'first txt entry', 'plainText' => 'txt text one']]);
});

it('asks for a data type when a file’s contents match more than one claiming its extension', function () {
    app(DataTypes::class)->register(TestDataType::class);

    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('lines.txt')))
        ->toThrow(Exception::class, 'The file could be read as CSV, Lines. Choose which data type to read it as.')
        ->and($this->import->getFormattedData(($this->fixturePath)('lines.txt'), new TestImporter()->dataType(TestDataType::class)))
        ->toBe([['line' => 'first line'], ['line' => 'second line']]);
});

it('reads a file with the settings of the data type it’s read as, ignoring those made for another', function () {
    app(DataTypes::class)->register(TestDataType::class);
    $importer = new TestImporter()->dataType(TestDataType::class);

    expect($this->import->getFormattedData(($this->fixturePath)('lines.txt'), $importer->dataTypeSettings(['column' => 'text'])))
        ->toBe([['text' => 'first line'], ['text' => 'second line']])
        ->and($this->import->getFormattedData(($this->fixturePath)('lines.txt'), $importer->dataTypeSettings(['type' => TestDataType::class, 'column' => 'text'])))
        ->toBe([['text' => 'first line'], ['text' => 'second line']])
        ->and($this->import->getFormattedData(($this->fixturePath)('lines.txt'), $importer->dataTypeSettings(['type' => Csv::class, 'column' => 'text'])))
        ->toBe([['line' => 'first line'], ['line' => 'second line']]);
});

it('rejects data type settings its rules don’t allow', function () {
    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('entries.csv'), new TestImporter()->dataTypeSettings(['delimiter' => 'colon'])))
        ->toThrow(Exception::class, 'The selected delimiter is invalid.');
});

it('rejects a chosen data type that a file’s contents don’t match', function () {
    app(DataTypes::class)->register(TestDataType::class);

    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('entries.txt'), new TestImporter()->dataType(TestDataType::class)))
        ->toThrow(Exception::class, 'The file can’t be read as Lines.');
});

it('leaves the reserved keys out of the source headings, wherever they’re nested', function () {
    $headings = $this->import->getDataHeadings(($this->fixturePath)('entries-reserved-keys.json'));

    expect(array_column($headings, 'value'))
        ->toContain('title', 'myMatchCriteria', 'blocks', 'blocks.heading')
        ->not->toContain(
            'matchCriteria',
            'matchCriteria.title',
            'clearableItems',
            'keepMissingNestedElements',
            'keepMissingNestedElements.blocks',
            'blocks.matchCriteria',
            'blocks.matchCriteria.heading',
        );
});

it('returns the source headings with a "Please select" option prepended', function () {
    $headings = $this->import->getDataHeadings(($this->fixturePath)('entries-plain-text.json'));

    expect($headings[0])->toEqual(new SourceColumn('Please select', ''))
        ->and(array_column($headings, 'value'))->toContain('title', 'plainText', 'slug');
});

it('keeps the error a data type throws while formatting', function () {
    $throwingType = new class extends TestDataType
    {
        #[Override]
        public static function format(string $data, array $settings = [], ?string $extension = null): array
        {
            throw new RuntimeException('The lines are tangled.');
        }
    };
    app(DataTypes::class)->register($throwingType::class);

    expect(fn () => $this->import->getFormattedData(($this->fixturePath)('lines.txt'), new TestImporter()->dataType($throwingType::class)))
        ->toThrow(Exception::class, 'The lines are tangled.');
});
