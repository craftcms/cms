<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Enums\MatchMode;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $field = Field::factory()->create([
        'name' => 'My Plain Text',
        'handle' => 'myPlainText',
        'type' => PlainText::class,
    ]);

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry(
        [CustomField::make($field->handle)],
        ['name' => 'With Plain Text', 'handle' => 'withPlainText'],
        entryAttrs: ['title' => 'existing entry', 'slug' => 'existing-entry'],
    );

    $this->importer = fn (MatchMode $mode) => new EntryImporter()
        ->site(Sites::getPrimarySite()->handle)
        ->matchCriteria(['title' => 'title'])
        ->matchMode($mode)
        ->transformer(null);

    $this->row = fn (string $title, string $text) => [
        'title' => $title,
        'sectionId' => $seed->section->handle,
        'typeId' => $seed->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
        'myPlainText' => $text,
    ];

    $this->entry = fn (string $title) => EntryElement::find()->title($title)->status(null)->one();
});

it('creates new elements and updates matched ones by default', function () {
    $importer = ($this->importer)(MatchMode::CreateAndUpdate);

    $this->import->importItem($importer, ($this->row)('existing entry', 'updated'));
    $this->import->importItem($importer, ($this->row)('new entry', 'created'));

    expect(($this->entry)('existing entry')->getFieldValue('myPlainText'))->toBe('updated')
        ->and(($this->entry)('new entry'))->not->toBeNull();
});

it('only creates new elements, leaving matched ones untouched', function () {
    $importer = ($this->importer)(MatchMode::CreateOnly);

    $this->import->importItem($importer, ($this->row)('existing entry', 'updated'));
    $this->import->importItem($importer, ($this->row)('new entry', 'created'));

    expect(($this->entry)('existing entry')->getFieldValue('myPlainText'))->toBeNull()
        ->and(($this->entry)('new entry'))->not->toBeNull();
});

it('only updates matched elements, creating nothing new', function () {
    $importer = ($this->importer)(MatchMode::UpdateOnly);

    $this->import->importItem($importer, ($this->row)('existing entry', 'updated'));
    $this->import->importItem($importer, ($this->row)('new entry', 'created'));

    expect(($this->entry)('existing entry')->getFieldValue('myPlainText'))->toBe('updated')
        ->and(($this->entry)('new entry'))->toBeNull();
});

it('falls back to creating and updating for an unknown mode', function () {
    expect(new EntryImporter()->matchMode('nonsense')->matchMode)->toBe(MatchMode::CreateAndUpdate);
});
