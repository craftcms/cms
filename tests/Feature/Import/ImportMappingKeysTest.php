<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Data\MappingValues;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $summary = ImportFixtures::plainTextField('summary');
    $body = ImportFixtures::plainTextField('body');
    $block = ImportFixtures::blockEntryType('textBlock', [$body]);
    ImportFixtures::matrixField('content', [$block]);

    $entryType = ImportFixtures::entryTypeWithTitle([
        CustomField::make('summary'),
        CustomField::make('content'),
    ]);
    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $uidOf = fn ($layout, string $handle) => collect($layout->getCustomFieldElements())
        ->first(fn ($element) => $element->attribute() === $handle)->uid;

    $entryType = EntryTypes::getEntryTypeById($entryType->id);
    $this->summaryUid = $uidOf($entryType->getFieldLayout(), 'summary');
    $this->contentUid = $uidOf($entryType->getFieldLayout(), 'content');
    $this->blockUid = $block->uid;
    $this->bodyUid = $uidOf(EntryTypes::getEntryTypeById($block->id)->getFieldLayout(), 'body');

    $this->importer = fn (array $settings) => new EntryImporter()
        ->site(Sites::getPrimarySite()->handle)
        ->section($section->uid)
        ->entryType($entryType->uid)
        ->map($settings['map'] ?? [])
        ->matchCriteria($settings['matchCriteria'] ?? []);
});

it('keys a mapping written with handles by uid for the mapping screen, nested fields included', function () {
    $values = MappingValues::fromImporter(($this->importer)([
        'map' => [
            'title' => 'title',
            'summary' => 'summary',
            'content' => ['textBlock' => ['title' => 'heading', 'fields' => ['body' => 'text']]],
        ],
        'matchCriteria' => ['summary' => '1'],
    ]));

    expect($values->map)->toBe([
        'title' => 'title',
        $this->summaryUid => 'summary',
        $this->contentUid => [$this->blockUid => ['title' => 'heading', 'fields' => [$this->bodyUid => 'text']]],
    ])->and($values->matchCriteria)->toBe([$this->summaryUid => 1]);
});

it('keeps the uid’s entry where a mapping has both a field’s handle and its uid', function () {
    $values = MappingValues::fromImporter(($this->importer)([
        'map' => ['summary' => 'old', $this->summaryUid => 'new'],
    ]));

    expect($values->map)->toBe([$this->summaryUid => 'new']);
});

it('leaves a mapping already keyed by uid as it is', function () {
    $map = [$this->summaryUid => 'summary', $this->contentUid => [$this->blockUid => ['fields' => [$this->bodyUid => 'text']]]];

    expect(MappingValues::fromImporter(($this->importer)(['map' => $map]))->map)->toBe($map);
});
