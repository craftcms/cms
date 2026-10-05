<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Events\ItemImported;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    Fields::refreshFields();

    $this->entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'With Plain Text', 'handle' => 'withPlainText'],
    );

    $this->section = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['minAuthors' => 0, 'maxAuthors' => 3]);

    $this->sectionRequiringAnAuthor = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['minAuthors' => 1, 'maxAuthors' => 3]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null)
        ->matchCriteria(['title' => 'title']);

    $this->entryData = fn (array $attributes = []) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
    ], $attributes);

    $this->importedEntry = fn () => EntryElement::find()->title('imported entry')->status(null)->one();
});

it('imports a supplied slug', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['slug' => 'a-supplied-slug']));

    expect(($this->importedEntry)()->slug)->toBe('a-supplied-slug');
});

it('generates a slug from the title when none is supplied', function () {
    $this->import->importItem($this->importer, ($this->entryData)());

    expect(($this->importedEntry)()->slug)->toBe('imported-entry');
});

it('imports postDate and expiryDate', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'postDate' => '2023-05-07 13:14:15',
        'expiryDate' => '2030-05-07 13:14:15',
    ]));

    $entry = ($this->importedEntry)();

    // Offset-less dates come back in the display timezone, so compare in that.
    $inAppTimeZone = fn ($date) => $date->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');

    expect($inAppTimeZone($entry->postDate))->toBe('2023-05-07 13:14:15')
        ->and($inAppTimeZone($entry->expiryDate))->toBe('2030-05-07 13:14:15');
});

it('imports a date whose key only auto-matches the property', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'Expiry Date' => '2030-05-07 13:14:15',
    ]));

    $expiryDate = ($this->importedEntry)()->expiryDate;

    expect($expiryDate?->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'))->toBe('2030-05-07 13:14:15');
});

it('leaves an entry live when its post date has passed and its expiry date has not', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'postDate' => now()->subDay()->format('Y-m-d H:i:s'),
        'expiryDate' => now()->addYear()->format('Y-m-d H:i:s'),
    ]));

    expect(($this->importedEntry)()->getStatus())->toBe(EntryElement::STATUS_LIVE);
});

it('makes an entry pending when its post date is in the future', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'postDate' => now()->addYear()->format('Y-m-d H:i:s'),
    ]));

    expect(($this->importedEntry)()->getStatus())->toBe(EntryElement::STATUS_PENDING);
});

it('makes an entry expired when its expiry date has passed', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'postDate' => now()->subYear()->format('Y-m-d H:i:s'),
        'expiryDate' => now()->subDay()->format('Y-m-d H:i:s'),
    ]));

    expect(($this->importedEntry)()->getStatus())->toBe(EntryElement::STATUS_EXPIRED);
});

it('imports an entry as disabled when enabled is false', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['enabled' => false]));

    $entry = ($this->importedEntry)();

    expect($entry->enabled)->toBeFalse()
        ->and($entry->getStatus())->toBe(EntryElement::STATUS_DISABLED);
});

it('imports multiple authorIds in the order given', function () {
    // With maxAuthors set, EntryRules checks each author's permission.
    $first = User::factory()->admin()->create();
    $second = User::factory()->admin()->create();

    $this->import->importItem($this->importer, ($this->entryData)([
        'authorIds' => [$second->id, $first->id],
    ]));

    expect(($this->importedEntry)()->getAuthorIds())->toBe([$second->id, $first->id]);
});

it('defaults the author to the logged-in user when authorIds is absent', function () {
    $user = User::factory()->admin()->create();
    actingAs($user);

    $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => $this->sectionRequiringAnAuthor->handle,
    ]));

    expect(($this->importedEntry)()->getAuthorIds())->toBe([$user->id]);
});

it('skips an entry that omits authorIds in a section requiring an author when nobody is logged in', function () {
    Event::fake([ItemImported::class]);

    expect(fn () => $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => $this->sectionRequiringAnAuthor->handle,
    ])))->toThrow(InvalidElementException::class);

    expect(($this->importedEntry)())->toBeNull();
    Event::assertNotDispatched(ItemImported::class);
});

it('passes the newly saved entry to the item imported event', function () {
    Event::fake([ItemImported::class]);

    $this->import->importItem($this->importer, ($this->entryData)());

    $entryId = ($this->importedEntry)()->id;

    Event::assertDispatched(fn (ItemImported $event) => $event->importedItem instanceof EntryElement
        && $event->importedItem->id === $entryId);
});

it('resolves sectionId and typeId given as numeric IDs', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => $this->section->id,
        'typeId' => $this->entryType->id,
    ]));

    $entry = ($this->importedEntry)();

    expect($entry->sectionId)->toBe($this->section->id)
        ->and($entry->getTypeId())->toBe($this->entryType->id);
});

it('resolves sectionId and typeId given as numeric strings', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => (string) $this->section->id,
        'typeId' => (string) $this->entryType->id,
    ]));

    $entry = ($this->importedEntry)();

    expect($entry->sectionId)->toBe($this->section->id)
        ->and($entry->getTypeId())->toBe($this->entryType->id);
});

it('resolves sectionId and typeId given as handles', function () {
    $this->import->importItem($this->importer, ($this->entryData)());

    $entry = ($this->importedEntry)();

    expect($entry->sectionId)->toBe($this->section->id)
        ->and($entry->getTypeId())->toBe($this->entryType->id);
});

it('imports an entry under a parent in a structure section', function () {})->todo();
