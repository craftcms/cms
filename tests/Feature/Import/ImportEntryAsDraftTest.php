<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Import\AssetImporter;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Data\MappingColumn;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Import\UserImporter;

beforeEach(function () {
    $this->import = app(Import::class);

    $textField = ImportFixtures::plainTextField('myPlainText', 'My Plain Text');
    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry([CustomField::make($textField->handle)]);

    $this->seedEntry = $seed->entry;

    $this->importer = new EntryImporter()
        ->site(Sites::getPrimarySite()->handle)
        ->section($seed->section->handle)
        ->entryType($seed->entryType->handle)
        ->transformer(null)
        ->matchCriteria(['title' => 'title']);

    $this->entryData = fn (array $values = []) => array_merge([
        'title' => 'imported entry',
        'myPlainText' => 'original text',
        'matchCriteria' => ['title' => 'title'],
    ], $values);

    $this->unpublishedDrafts = fn (string $title) => EntryElement::find()
        ->drafts()
        ->draftOf(false)
        ->title($title)
        ->status(null)
        ->all();

    $this->canonicalEntries = fn (string $title) => EntryElement::find()
        ->title($title)
        ->status(null)
        ->all();
});

it('creates an unpublished draft when the row is flagged as a draft', function (mixed $flag) {
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => $flag]));

    $drafts = ($this->unpublishedDrafts)('imported entry');

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->getFieldValue('myPlainText'))->toBe('original text')
        ->and(($this->canonicalEntries)('imported entry'))->toBeEmpty();
})->with([
    'bool' => [true],
    'numeric string' => ['1'],
    'word' => ['yes'],
]);

it('creates a normal entry when the row is not flagged as a draft', function (mixed $flag) {
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => $flag]));

    expect(($this->canonicalEntries)('imported entry'))->toHaveCount(1)
        ->and(($this->unpublishedDrafts)('imported entry'))->toBeEmpty();
})->with([
    'false' => [false],
    'zero' => ['0'],
    'blank' => [''],
]);

it('updates the existing unpublished draft when a draft row is re-imported', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => true]));
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => true, 'myPlainText' => 'updated text']));

    $drafts = ($this->unpublishedDrafts)('imported entry');

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->getFieldValue('myPlainText'))->toBe('updated text')
        ->and(($this->canonicalEntries)('imported entry'))->toBeEmpty();
});

it('publishes an existing unpublished draft when the row is no longer flagged as a draft', function (array $values) {
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => true]));
    $draftId = ($this->unpublishedDrafts)('imported entry')[0]->id;

    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => false, ...$values]));

    $entries = ($this->canonicalEntries)('imported entry');

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->id)->toBe($draftId)
        ->and($entries[0]->getFieldValue('myPlainText'))->toBe($values['myPlainText'] ?? 'original text')
        ->and(($this->unpublishedDrafts)('imported entry'))->toBeEmpty();
})->with([
    'unchanged data' => [[]],
    'changed data' => [['myPlainText' => 'updated text']],
]);

it('updates an existing entry in place when the row is flagged as a draft', function (bool $enabled) {
    $this->seedEntry->enabled = $enabled;
    expect(Elements::saveElement($this->seedEntry))->toBeTrue();

    $this->import->importItem($this->importer, ($this->entryData)([
        'title' => 'seed entry',
        'saveAsDraft' => true,
        'myPlainText' => 'updated text',
    ]));

    $entry = EntryElement::find()->id($this->seedEntry->id)->status(null)->one();

    expect($entry->enabled)->toBe($enabled)
        ->and($entry->getFieldValue('myPlainText'))->toBe('updated text')
        ->and(EntryElement::find()->drafts()->status(null)->title('seed entry')->all())->toBeEmpty();
})->with([
    'enabled' => [true],
    'disabled' => [false],
]);

it('updates the canonical entry rather than one of its drafts', function () {
    $draft = Drafts::createDraft($this->seedEntry);

    $this->import->importItem($this->importer, ($this->entryData)([
        'title' => 'seed entry',
        'myPlainText' => 'updated text',
    ]));

    expect(EntryElement::find()->id($this->seedEntry->id)->status(null)->one()->getFieldValue('myPlainText'))->toBe('updated text')
        ->and(EntryElement::find()->drafts()->id($draft->id)->status(null)->one()->getFieldValue('myPlainText'))->not->toBe('updated text');
});

it('offers the draft option on the mapping screen only for element types that support drafts', function (string $importerClass, bool $offered) {
    $handles = collect(new $importerClass()->getDestinationCols())
        ->whereInstanceOf(MappingColumn::class)
        ->pluck('handle');

    expect($handles->contains('saveAsDraft'))->toBe($offered);
})->with([
    'Entry' => [EntryImporter::class, true],
    'Asset' => [AssetImporter::class, false],
    'User' => [UserImporter::class, false],
]);

it('leaves a matched unpublished draft as a draft when the draft option is not mapped', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => true]));
    $this->import->importItem($this->importer, ($this->entryData)(['myPlainText' => 'updated text']));

    $drafts = ($this->unpublishedDrafts)('imported entry');

    expect($drafts)->toHaveCount(1)
        ->and($drafts[0]->getFieldValue('myPlainText'))->toBe('updated text')
        ->and(($this->canonicalEntries)('imported entry'))->toBeEmpty();
});

it('prefers an existing entry over an unpublished draft matching the same criteria', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => true, 'postDate' => '2099-01-01 00:00:00']));
    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => false, 'title' => 'other entry']));

    $entry = ($this->canonicalEntries)('other entry')[0];
    $entry->title = 'imported entry';
    expect(Elements::saveElement($entry))->toBeTrue();

    $this->import->importItem($this->importer, ($this->entryData)(['saveAsDraft' => true, 'myPlainText' => 'updated text']));

    expect(EntryElement::find()->id($entry->id)->status(null)->one()->getFieldValue('myPlainText'))->toBe('updated text')
        ->and(($this->unpublishedDrafts)('imported entry')[0]->getFieldValue('myPlainText'))->toBe('original text');
});

it('decides whether to save as a draft from a mapping template', function (string $status, bool $isDraft) {
    $importer = $this->importer
        ->map(['title' => 'name', 'saveAsDraft' => ImportHelper::TEMPLATE_RULE])
        ->templates(['saveAsDraft' => "{% if object.status == 'draft' %}1{% endif %}"]);

    $this->import->importItem($importer, ['name' => 'templated entry', 'status' => $status]);

    expect(($this->unpublishedDrafts)('templated entry'))->toHaveCount($isDraft ? 1 : 0)
        ->and(($this->canonicalEntries)('templated entry'))->toHaveCount($isDraft ? 0 : 1);
})->with([
    'draft' => ['draft', true],
    'live' => ['live', false],
]);
