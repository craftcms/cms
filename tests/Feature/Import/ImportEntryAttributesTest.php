<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    Fields::refreshFields();

    $this->entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'With Plain Text', 'handle' => 'withPlainText'],
    );

    // maxAuthors 3 so the multi-author row is valid; minAuthors 0 so rows may omit authors
    $this->section = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['minAuthors' => 0, 'maxAuthors' => 3]);

    // the author default only kicks in when exactly one author is required
    $this->sectionRequiringAnAuthor = Section::factory()
        ->withEntryTypes($this->entryType)
        ->create(['minAuthors' => 1, 'maxAuthors' => 3]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
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

// slugs

it('imports a supplied slug', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['slug' => 'a-supplied-slug']));

    expect(($this->importedEntry)()->slug)->toBe('a-supplied-slug');
});

it('generates a slug from the title when none is supplied', function () {
    $this->import->importItem($this->importer, ($this->entryData)());

    expect(($this->importedEntry)()->slug)->toBe('imported-entry');
});

// post/expiry dates and the statuses they produce

it('imports postDate and expiryDate', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'postDate' => '2023-05-07 13:14:15',
        'expiryDate' => '2030-05-07 13:14:15',
    ]));

    $entry = ($this->importedEntry)();

    // a date string without an offset is read in the app timezone and handed back in the system's
    // display timezone, so compare in the timezone it came in as
    $inAppTimeZone = fn ($date) => $date->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');

    expect($inAppTimeZone($entry->postDate))->toBe('2023-05-07 13:14:15')
        ->and($inAppTimeZone($entry->expiryDate))->toBe('2030-05-07 13:14:15');
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

// authors

it('imports multiple authorIds in the order given', function () {
    // once a section sets maxAuthors, EntryRules checks each author's 'author' permission
    $first = User::factory()->admin()->create();
    $second = User::factory()->admin()->create();

    $this->import->importItem($this->importer, ($this->entryData)([
        'authorIds' => [$second->id, $first->id],
    ]));

    expect(($this->importedEntry)()->getAuthorIds())->toBe([$second->id, $first->id]);
});

// Entry::maybeSetDefaultAttributes() defaults the author to the current user when the section
// requires exactly one author, so this only works with somebody logged in.
it('defaults the author to the logged-in user when authorIds is absent', function () {
    $user = User::factory()->admin()->create();
    actingAs($user);

    $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => $this->sectionRequiringAnAuthor->handle,
    ]));

    expect(($this->importedEntry)()->getAuthorIds())->toBe([$user->id]);
});

// the CLI case: no authenticated user, so there's nothing to default from and the entry fails the
// section's author requirement
it('skips an entry that omits authorIds in a section requiring an author when nobody is logged in', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => $this->sectionRequiringAnAuthor->handle,
    ]));

    expect(($this->importedEntry)())->toBeNull();
});

// section/type references

it('resolves sectionId and typeId given as numeric IDs', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'sectionId' => $this->section->id,
        'typeId' => $this->entryType->id,
    ]));

    $entry = ($this->importedEntry)();

    expect($entry->sectionId)->toBe($this->section->id)
        ->and($entry->getTypeId())->toBe($this->entryType->id);
});

// the manual fixtures send IDs as strings ("typeId": "1"), so both forms have to resolve
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

it('imports an entry under a parent in a structure section', function () {
    // pending entry parent support - see the todo in ImportHelper::getImportableProperties()
})->todo();
