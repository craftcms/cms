<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Entry\Import\EntryTransformer;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\Users as UsersField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->import = app(Import::class);

    $allFields = [
        Field::factory()->create(['name' => 'My Entries', 'handle' => 'myEntries', 'type' => EntriesField::class]),
        Field::factory()->create(['name' => 'My Users', 'handle' => 'myUsers', 'type' => UsersField::class]),
        Field::factory()->create(['name' => 'My Assets', 'handle' => 'myAssets', 'type' => AssetsField::class]),
    ];

    Fields::refreshFields();

    $this->relatedUser = User::factory()->createElement();
    $this->relatedAsset = Asset::factory()->createElement();

    $layoutElements = array_map(fn ($field) => CustomField::make($field->handle), $allFields);

    $seed = ImportFixtures::seedEntry($layoutElements, ['name' => 'With Relation Fields', 'handle' => 'withRelationFields']);

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $relatedResult = Entry::factory()
        ->forSection($seed->section)
        ->forEntryType($seed->entryType)
        ->withFieldLayout($seed->fieldLayout)
        ->createElementWithFields(['title' => 'related entry', 'slug' => 'related-entry']);

    $this->relatedEntry = $relatedResult->element;

    $this->importer = new EntryImporter()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (array $fieldValues) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
    ], $fieldValues);
});

it('imports an entries field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

it('splits a CSV cell into an entries field’s values with the step’s list separator', function () {
    $otherEntry = Entry::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElementWithFields(['title' => 'other entry', 'slug' => 'other-entry'])
        ->element;
    $root = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    try {
        $this->importer->source('tests/Fixtures/Import/entries.csv')->dataTypeSettings(['listSeparator' => '|']);
        $this->import->importItem($this->importer, ($this->entryData)(['myEntries' => "{$otherEntry->id}|{$this->relatedEntry->id}"]));
    } finally {
        Aliases::set('@root', $root);
    }

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$otherEntry->id, $this->relatedEntry->id]);
});

it('imports a users field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myUsers' => [$this->relatedUser->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myUsers')->ids())->toBe([$this->relatedUser->id]);
});

it('imports an assets field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myAssets' => [$this->relatedAsset->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myAssets')->ids())->toBe([$this->relatedAsset->id]);
});

it('imports an entries field value using a transformer with ID map', function () {
    $importer = (clone $this->importer)
        ->transformer(new class extends EntryTransformer
        {
            public function transform(array $item, BaseImporter $importer, mixed $element): array
            {
                $array = parent::transform($item, $importer, $element);
                $array['myEntries'] = $item['myEntriesToBeMapped'];

                return $array;
            }
        });

    $this->import->importItem($importer, ($this->entryData)(['myEntriesToBeMapped' => [$this->relatedEntry->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

it('imports an entries field value using a transformer with element lookup', function () {
    $importer = (clone $this->importer)
        ->transformer(new class extends EntryTransformer
        {
            public function transform(array $item, BaseImporter $importer, mixed $element): array
            {
                $array = parent::transform($item, $importer, $element);
                $array['myEntries'] = EntryElement::find()->title($item['myEntriesToBeMapped'])->ids();

                return $array;
            }
        });

    $this->import->importItem($importer, ($this->entryData)(['myEntriesToBeMapped' => [$this->relatedEntry->title]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

it('preserves the order of related element ids', function () {
    $second = Entry::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElementWithFields(['title' => 'second related entry', 'slug' => 'second-related-entry'])
        ->element;

    $this->import->importItem($this->importer, ($this->entryData)([
        'myEntries' => [$second->id, $this->relatedEntry->id],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([$second->id, $this->relatedEntry->id]);
});

it('leaves existing relations alone when the field is absent from a later import', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)([]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

it('leaves existing relations alone when an empty list is provided and the field is not clearable', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)(['myEntries' => []]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

// An empty list becomes null, then an empty list again, which clears relations.
it('clears existing relations when an empty list is provided for a clearable field', function () {
    $importer = (clone $this->importer)
        ->matchCriteria(['title' => 'title'])
        ->clearableItems(['myEntries']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)(['myEntries' => []]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([]);
});

it('clears existing relations when a clearable field is absent from a later import', function () {
    $importer = (clone $this->importer)
        ->matchCriteria(['title' => 'title'])
        ->clearableItems(['myEntries']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)([]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([]);
});

it('clears an assets field marked clearable', function () {
    $importer = (clone $this->importer)
        ->matchCriteria(['title' => 'title'])
        ->clearableItems(['myAssets']);

    $this->import->importItem($importer, ($this->entryData)(['myAssets' => [$this->relatedAsset->id]]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myAssets')->ids())->toBe([$this->relatedAsset->id]);

    $this->import->importItem($importer, ($this->entryData)(['myAssets' => []]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myAssets')->ids())->toBe([]);
});

describe('matching related elements by an attribute', function () {
    beforeEach(function () {
        $this->createEntry = fn (string $title, ?string $slug = null, ?Section $section = null, bool $enabled = true) => EntryElement::find()
            ->id(Entry::factory()
                ->forSection($section ?? $this->section)
                ->forEntryType($this->entryType)
                ->title($title)
                ->slug($slug ?? Str::slug($title))
                ->enabled($enabled)
                ->create()
                ->id)
            ->status(null)
            ->one();

        $this->importEntries = function (mixed $value, string $matchBy = 'title', string $field = 'myEntries') {
            $importer = (clone $this->importer)->fieldSettings([$field => ['matchBy' => $matchBy]]);
            $this->import->importItem($importer, ($this->entryData)([$field => $value]));

            // relations to disabled elements are only listed without the default status
            return EntryElement::find()->title('imported entry')->one()->getFieldValue($field)->status(null)->ids();
        };
    });

    it('relates entries by title, ignoring case and keeping the incoming order', function () {
        $other = ($this->createEntry)('Rock, Paper');

        expect(($this->importEntries)(['rock, paper', 'RELATED ENTRY']))->toBe([$other->id, $this->relatedEntry->id]);
    });

    it('relates entries by slug', function () {
        expect(($this->importEntries)(['related-entry'], 'slug'))->toBe([$this->relatedEntry->id]);
    });

    it('relates users by username and email', function () {
        $user = User::factory()->createElement();

        expect(($this->importEntries)([$user->username], 'username', 'myUsers'))->toBe([$user->id])
            ->and(($this->importEntries)([strtoupper($user->email)], 'email', 'myUsers'))->toBe([$user->id]);
    });

    it('reads a numeric value as a title when matching by title', function () {
        $numbered = ($this->createEntry)($this->relatedEntry->id.'0');

        expect(($this->importEntries)([$this->relatedEntry->id.'0']))->toBe([$numbered->id]);
    });

    it('relates the first live match, then falls back to the first match in any status', function () {
        ($this->createEntry)('twin', 'twin-disabled', enabled: false);
        $live = ($this->createEntry)('twin', 'twin-live');
        $firstDisabled = ($this->createEntry)('lonely', 'lonely-1', enabled: false);
        ($this->createEntry)('lonely', 'lonely-2', enabled: false);

        expect(($this->importEntries)(['twin', 'lonely']))->toBe([$live->id, $firstDisabled->id]);
    });

    it('only relates entries from the sections the field allows, skipping and logging the rest', function () {
        $otherSection = Section::factory()->withEntryTypes($this->entryType)->create(['minAuthors' => 0]);
        ($this->createEntry)('elsewhere', section: $otherSection);
        Fields::saveField(tap(Fields::getFieldByHandle('myEntries'), fn (EntriesField $field) => $field->sources = ["section:{$this->section->uid}"]));
        Fields::refreshFields();
        ImportLog::spy();

        expect(($this->importEntries)(['elsewhere', 'related entry']))->toBe([$this->relatedEntry->id]);

        ImportLog::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, '“elsewhere”'))->once();
    });

    it('relates existing assets by filename and title without uploading anything', function () {
        $byTitle = Asset::factory()
            ->state(fn (array $attributes) => ['id' => $attributes['id']->title('Mountain View')])
            ->createElement();
        $assetCount = Asset::query()->count();

        expect(($this->importEntries)([$this->relatedAsset->filename], 'filename', 'myAssets'))->toBe([$this->relatedAsset->id])
            ->and(($this->importEntries)(['mountain view'], 'title', 'myAssets'))->toBe([$byTitle->id])
            ->and(Asset::query()->count())->toBe($assetCount);
    });

    it('relates an asset by filename from a subfolder of a volume', function () {
        $rootFolder = VolumeFolder::query()->find($this->relatedAsset->folderId);
        $subfolder = VolumeFolder::factory()->create([
            'volumeId' => $rootFolder->volumeId,
            'parentId' => $rootFolder->id,
            'path' => 'nested/',
        ]);
        $nested = Asset::factory()->createElement([
            'volumeId' => $rootFolder->volumeId,
            'folderId' => $subfolder->id,
            'filename' => 'nested-only.jpg',
        ]);

        expect(($this->importEntries)(['nested-only.jpg'], 'filename', 'myAssets'))->toBe([$nested->id]);
    });

    it('relates by ID unless asked to match by another attribute', function () {
        expect(($this->importEntries)([$this->relatedEntry->id], 'id'))->toBe([$this->relatedEntry->id]);
    });

    it('offers the attributes each relation field can match by', function () {
        $options = fn (string $handle) => array_column(
            Fields::getFieldByHandle($handle)->importMappingSettings()[0]->options ?? [],
            'value',
        );

        expect($options('myEntries'))->toBe(['id', 'title', 'slug'])
            ->and($options('myUsers'))->toBe(['id', 'username', 'email'])
            ->and($options('myAssets'))->toBe(['id', 'title', 'filename']);
    });
});
