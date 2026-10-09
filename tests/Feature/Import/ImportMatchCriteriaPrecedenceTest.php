<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Entry\Import\EntryTransformer;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    $blockEntryType = ImportFixtures::blockEntryType('secondEt', [$plainTextField], 'Second ET');
    ImportFixtures::matrixField('myMatrix', [$blockEntryType], 'My Matrix');

    $seed = ImportFixtures::seedEntry(
        [CustomField::make('myMatrix')],
        ['name' => 'With Matrix Field', 'handle' => 'withMatrixField'],
    );

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (string $title, array $extra = []) => [
        'title' => $title,
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        ...$extra,
    ];

    $this->import->importItem($this->importer, ($this->entryData)('entry A'));
    $this->import->importItem($this->importer, ($this->entryData)('entry B'));

    $this->entryA = EntryElement::find()->title('entry A')->one();
    $this->entryB = EntryElement::find()->title('entry B')->one();

    $this->transformerMatching = function (array $matchCriteria): EntryTransformer {
        $transformer = new class extends EntryTransformer
        {
            public array $matchCriteria;

            public function additionalMatchCriteria(BaseImporter $importer, array $data): array
            {
                return $this->matchCriteria;
            }
        };
        $transformer->matchCriteria = $matchCriteria;

        return $transformer;
    };

    $this->importBlocksXAndY = function (BaseImporter $importer): array {
        ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)('entry A', [
            'myMatrix' => [
                ['type' => 'secondEt', 'title' => 'block X', 'fields' => ['plainText' => 'x']],
                ['type' => 'secondEt', 'title' => 'block Y', 'fields' => ['plainText' => 'y']],
            ],
        ]));

        return EntryElement::find()->id($this->entryA->id)->one()->getFieldValue('myMatrix')->all();
    };
});

it('matches on in-data criteria when no other criteria are set', function () {
    $this->import->importItem($this->importer, ($this->entryData)('updated', [
        'matchCriteria' => ['id' => $this->entryB->id],
    ]));

    expect(EntryElement::find()->id($this->entryB->id)->one()->title)->toBe('updated')
        ->and(EntryElement::find()->id($this->entryA->id)->one()->title)->toBe('entry A');
});

it('lets step-level criteria override in-data criteria', function () {
    $importer = (clone $this->importer)->matchCriteria(['id' => 'id']);

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)('updated', [
        'id' => $this->entryA->id,
        'matchCriteria' => ['id' => $this->entryB->id],
    ]));

    expect(EntryElement::find()->id($this->entryA->id)->one()->title)->toBe('updated')
        ->and(EntryElement::find()->id($this->entryB->id)->one()->title)->toBe('entry B');
});

it('lets transformer criteria override step-level criteria', function () {
    $importer = (clone $this->importer)
        ->matchCriteria(['id' => 'id'])
        ->transformer(($this->transformerMatching)(['id' => $this->entryB->id]));

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)('updated', [
        'id' => $this->entryA->id,
    ]));

    expect(EntryElement::find()->id($this->entryB->id)->one()->title)->toBe('updated')
        ->and(EntryElement::find()->id($this->entryA->id)->one()->title)->toBe('entry A');
});

it('lets transformer criteria override in-data criteria', function () {
    $importer = (clone $this->importer)->transformer(($this->transformerMatching)(['id' => $this->entryB->id]));

    $this->import->importItem($importer, ($this->entryData)('updated', [
        'matchCriteria' => ['id' => $this->entryA->id],
    ]));

    expect(EntryElement::find()->id($this->entryB->id)->one()->title)->toBe('updated')
        ->and(EntryElement::find()->id($this->entryA->id)->one()->title)->toBe('entry A');
});

it('lets step-level criteria override in-data criteria on nested blocks', function () {
    $importer = (clone $this->importer)->matchCriteria([
        'title' => 'title',
        'myMatrix' => [
            'secondEt' => ['id' => 'id'],
        ],
    ]);

    [$blockX, $blockY] = ($this->importBlocksXAndY)($importer);

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)('entry A', [
        'myMatrix' => [
            [
                'type' => 'secondEt',
                'id' => $blockX->id,
                'title' => 'updated',
                'matchCriteria' => ['id' => $blockY->id],
                'fields' => ['plainText' => 'x'],
            ],
        ],
    ]));

    $blocks = EntryElement::find()->id($this->entryA->id)->one()->getFieldValue('myMatrix')->all();

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]->id)->toBe($blockX->id)
        ->and($blocks[0]->title)->toBe('updated');
});

it('lets transformer criteria override step-level criteria on nested blocks', function () {
    $importer = (clone $this->importer)->matchCriteria([
        'title' => 'title',
        'myMatrix' => [
            'secondEt' => ['id' => 'id'],
        ],
    ]);

    [$blockX, $blockY] = ($this->importBlocksXAndY)($importer);

    $importer = (clone $importer)->transformer(($this->transformerMatching)([
        'myMatrix' => [
            'secondEt' => ['id' => $blockY->id],
        ],
    ]));

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)('entry A', [
        'myMatrix' => [
            [
                'type' => 'secondEt',
                'id' => $blockX->id,
                'title' => 'updated',
                'fields' => ['plainText' => 'y'],
            ],
        ],
    ]));

    $blocks = EntryElement::find()->id($this->entryA->id)->one()->getFieldValue('myMatrix')->all();

    expect(EntryElement::find()->title('entry A')->count())->toBe(1)
        ->and($blocks)->toHaveCount(1)
        ->and($blocks[0]->id)->toBe($blockY->id)
        ->and($blocks[0]->title)->toBe('updated');
});

it('matches on incoming values literally rather than as query param syntax', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)('*'));

    expect(EntryElement::find()->id($this->entryA->id)->one()->title)->toBe('entry A')
        ->and(EntryElement::find()->id($this->entryB->id)->one()->title)->toBe('entry B')
        ->and(EntryElement::find()->title('\*')->count())->toBe(1);
});
