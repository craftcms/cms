<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryTransformer;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

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

    $this->command = fn (string $file, array $options = []) => $this->artisan('craft:import:entry', [
        'file' => 'tests/Fixtures/Import/'.$file,
        '--site' => Sites::getPrimarySite()->handle,
        '--transformer' => EntryTransformer::class,
        '--section' => $this->section->uid,
        '--entryType' => $this->entryType->uid,
        ...$options,
    ]);
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('imports every row of a JSON file', function () {
    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))->toBe('text from the file');
});

it('updates the same entries on a second run instead of duplicating them', function () {
    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    $entryId = EntryElement::find()->title('first file entry')->one()->id;

    ($this->command)('entries-plain-text-updated.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    $entry = EntryElement::find()->title('first file entry')->one();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and($entry->id)->toBe($entryId)
        ->and($entry->getFieldValue('plainText'))->toBe('UPDATED text from the file');
});

it('fails with a validation error when the file does not exist', function () {
    ($this->command)('does-not-exist.json', ['--transformer' => 'null', '--matchCriteria' => '={"title":"title"}'])
        ->assertFailed();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(0);
});

it('accepts match criteria entered through the prompt with a leading "="', function () {
    ($this->command)('entries-plain-text.json', ['--transformer' => EntryTransformer::class])
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '={"title":"title"}')
        ->assertSuccessful();

    ($this->command)('entries-plain-text-updated.json', ['--transformer' => EntryTransformer::class])
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '={"title":"title"}')
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))
        ->toBe('UPDATED text from the file');
});

it('prompts for the transformer when the option is omitted, defaulting it when left empty', function () {
    $this->artisan('craft:import:entry', [
        'file' => 'tests/Fixtures/Import/entries-plain-text.json',
        '--site' => Sites::getPrimarySite()->handle,
        '--matchCriteria' => '={"title":"title"}',
        '--section' => $this->section->uid,
        '--entryType' => $this->entryType->uid,
    ])
        ->expectsQuestion(TRANSFORMER_QUESTION, '')
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(3)
        // the defaulted transformer still has to map the rows' values, not just create entries
        ->and(EntryElement::find()->title('first file entry')->one()->getFieldValue('plainText'))->toBe('text from the file');
});

it('throws for an unknown site handle', function () {
    ($this->command)('entries-plain-text.json', ['--site' => 'no-such-site', '--matchCriteria' => '={"title":"title"}'])
        ->assertFailed();
});
