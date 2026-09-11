<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Transformers\EntryTransformer;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

/** The field layout prompt can't be skipped with an option: an empty value is falsy, so the form still asks. */
const FIELD_LAYOUT_QUESTION = 'Provide UID, ID, or type of the field layout provider you want to use.';

const TRANSFORMER_QUESTION = 'The transformer you want to use to manipulate the data on import (fully qualified class name for the transformer)';

const MATCH_CRITERIA_QUESTION = 'A JSON-encoded array of match criteria you’d like to use to match against existing elements. If none provided, ID will be used for matching.';

beforeEach(function () {
    // resolvedFilePath() resolves against @root, which points at the Testbench skeleton in tests
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 4));

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    Fields::refreshFields();

    // the fixture files reference these handles, so they're pinned rather than generated
    $this->entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'Fixture Type', 'handle' => 'fixtureType'],
    );
    $this->otherEntryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'Other Type', 'handle' => 'otherType'],
    );
    $this->section = Section::factory()
        ->withEntryTypes($this->entryType, $this->otherEntryType)
        ->create(['handle' => 'fixtureSection', 'minAuthors' => 0]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->command = fn (string $file, array $options = []) => $this->artisan('craft:import:element', [
        'elementType' => EntryElement::class,
        'file' => 'tests/Fixtures/Import/'.$file,
        '--site' => Sites::getPrimarySite()->handle,
        '--transformer' => EntryTransformer::class,
        ...$options,
    ]);
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('imports every row of a JSON file', function () {
    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))->toBe('text from the file');
});

it('updates the same entries on a second run instead of duplicating them', function () {
    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->assertSuccessful();

    $entryId = EntryElement::find()->title('first file entry')->one()->id;

    ($this->command)('entries-plain-text-updated.json', ['--matchCriteria' => '={"title":"title"}'])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->assertSuccessful();

    $entry = EntryElement::find()->title('first file entry')->one();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and($entry->id)->toBe($entryId)
        ->and($entry->getFieldValue('plainText'))->toBe('UPDATED text from the file');
});

it('imports the entry type named in each row when no field layout provider is chosen', function () {
    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->assertSuccessful();

    expect(EntryElement::find()->title('first file entry')->one()->getType()->handle)->toBe('fixtureType');
});

// Entry::setAttributesForImport() drops the incoming typeId whenever the importer has a field
// layout, so a provider chosen on the CLI wins over each row's own typeId - the same precedence
// ImportEntryTest asserts for a saved config.
it('overrides each row’s entry type with --fieldLayoutProvider', function () {
    $layoutUid = EntryTypes::getEntryTypeById($this->otherEntryType->id)->getFieldLayout()->uid;

    ($this->command)('entries-plain-text.json', [
        '--matchCriteria' => '={"title":"title"}',
        '--fieldLayoutProvider' => $layoutUid,
    ])->assertSuccessful();

    expect(EntryElement::find()->title('first file entry')->one()->getType()->handle)->toBe('otherType');
});

it('fails with a validation error when the file does not exist', function () {
    ($this->command)('does-not-exist.json', ['--matchCriteria' => '={"title":"title"}'])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->assertFailed();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(0);
});

// The answer needs no leading "=" - Element::handle() adds one before decoding, which is the only
// difference from passing --matchCriteria.
it('uses match criteria entered through the prompt', function () {
    $run = fn () => ($this->command)('entries-plain-text.json', ['--transformer' => EntryTransformer::class])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '{"title":"title"}')
        ->assertSuccessful();

    $run();
    $entryId = EntryElement::find()->title('first file entry')->one()->id;

    $run();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and(EntryElement::find()->title('first file entry')->one()->id)->toBe($entryId);
});

it('accepts match criteria entered through the prompt with a leading "="', function () {
    ($this->command)('entries-plain-text.json', ['--transformer' => EntryTransformer::class])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '={"title":"title"}')
        ->assertSuccessful();

    ($this->command)('entries-plain-text-updated.json', ['--transformer' => EntryTransformer::class])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '={"title":"title"}')
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))
        ->toBe('UPDATED text from the file');
});

it('prompts for the transformer when the option is omitted, defaulting it when left empty', function () {
    $this->artisan('craft:import:element', [
        'elementType' => EntryElement::class,
        'file' => 'tests/Fixtures/Import/entries-plain-text.json',
        '--site' => Sites::getPrimarySite()->handle,
        '--matchCriteria' => '={"title":"title"}',
    ])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->expectsQuestion(TRANSFORMER_QUESTION, '')
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        // the defaulted transformer still has to map the rows' values, not just create entries
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))->toBe('text from the file');
});

// An unknown site is a hard error rather than a reported configuration problem: site() throws as
// the config is built, before the validation pass that collects and prints errors.
it('throws for an unknown site handle', function () {
    ($this->command)('entries-plain-text.json', ['--site' => 'no-such-site', '--matchCriteria' => '={"title":"title"}'])
        ->expectsQuestion(FIELD_LAYOUT_QUESTION, '')
        ->run();
})->throws(InvalidArgumentException::class, 'No site found with handle or UID: "no-such-site".');
