<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryTransformer;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Import\Events\ImportStarted;
use CraftCms\Cms\Import\Events\ImportStepFinished;
use CraftCms\Cms\Import\Events\ImportStepStarted;
use CraftCms\Cms\Import\Events\ItemImported;
use CraftCms\Cms\Import\Events\ItemImporting;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

const TRANSFORMER_QUESTION = 'The transformer you want to use to manipulate the data on import (fully qualified class name for the transformer)';

const MATCH_CRITERIA_QUESTION = 'A JSON-encoded array of match criteria you’d like to use to match against existing elements. If none provided, ID will be used for matching.';

beforeEach(function () {
    // resolvedSourcePath() resolves against @root, which points at the Testbench skeleton in tests
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 4));

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    Fields::refreshFields();

    $blockEntryType = ImportFixtures::blockEntryType('blockEt', [$plainTextField], 'Block ET');
    $nestedMatrixField = ImportFixtures::matrixField('myNestedMatrix', [$blockEntryType], 'My Nested Matrix');
    $outerEntryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($nestedMatrixField->handle)],
        ['name' => 'Outer ET', 'handle' => 'outerEt'],
    );
    $matrixField = ImportFixtures::matrixField('myMatrix', [$blockEntryType, $outerEntryType], 'My Matrix');

    // the fixture files reference these handles, so they're pinned rather than generated
    $this->entryType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle), CustomField::make($matrixField->handle)],
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

    $this->command = fn (string $source, array $options = []) => $this->artisan('craft:import:entry', [
        'source' => 'tests/Fixtures/Import/'.$source,
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

it('creates duplicates on a second run when no match criteria is given', function () {
    ($this->command)('entries-plain-text.json')
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '')
        ->assertSuccessful();

    ($this->command)('entries-plain-text.json')
        ->expectsQuestion(MATCH_CRITERIA_QUESTION, '')
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(6);
});

it('imports a file’s matrix blocks', function () {
    ($this->command)('entries-matrix.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    $blocks = EntryElement::find()->title('file entry with matrix')->one()->getFieldValue('myMatrix')->all();

    expect($blocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->title, $blocks))->toBe(['file block 1', 'file block 2'])
        ->and($blocks[0]->getFieldValue('plainText'))->toBe('block one');
});

it('imports a file’s matrix-in-matrix blocks', function () {
    ($this->command)('entries-matrix-in-matrix.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    $outerBlock = EntryElement::find()->title('file entry with matrix in matrix')->one()->getFieldValue('myMatrix')->one();
    $innerBlocks = $outerBlock->getFieldValue('myNestedMatrix')->all();

    expect($outerBlock->title)->toBe('file outer block')
        ->and($innerBlocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->getFieldValue('plainText'), $innerBlocks))->toBe(['inner one', 'inner two']);
});

it('matches entries and their matrix blocks on a second run', function () {
    $options = ['--matchCriteria' => '={"title":"title","myMatrix":{"blockEt":{"title":"title"}}}'];

    ($this->command)('entries-matrix.json', $options)->assertSuccessful();

    $entry = EntryElement::find()->title('file entry with matrix')->one();
    $entryId = $entry->id;
    $blockIds = $entry->getFieldValue('myMatrix')->ids();

    ($this->command)('entries-matrix.json', $options)->assertSuccessful();

    $entry = EntryElement::find()->title('file entry with matrix')->one();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(1)
        ->and($entry->id)->toBe($entryId)
        ->and($entry->getFieldValue('myMatrix')->ids())->toBe($blockIds);
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
        'source' => 'tests/Fixtures/Import/entries-plain-text.json',
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

it('fires the import finished event once with the importer and the items’ run ID', function () {
    Event::fake([ImportFinished::class, ItemImported::class]);

    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    Event::assertDispatchedTimes(ImportFinished::class, 1);
    Event::assertDispatched(fn (ImportFinished $event) => $event->importPlan === null
        && count($event->steps) === 1
        && $event->steps[0] instanceof BaseImporter
        && Str::isUuid($event->runId)
        && $event->hasFailures === false);

    $runId = Event::dispatched(ImportFinished::class)->first()[0]->runId;
    Event::assertDispatchedTimes(ItemImported::class, 3);
    Event::assertNotDispatched(ItemImported::class, fn (ItemImported $event) => $event->runId !== $runId);
});

it('flags the import finished event when an item fails to import', function () {
    Event::fake([ImportFinished::class]);
    Event::listen(ItemImporting::class, fn () => throw new Exception('Item failed.'));

    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    Event::assertDispatched(fn (ImportFinished $event) => $event->hasFailures);
});

it('fires the import and step lifecycle events in order with the same run ID', function () {
    $fired = [];
    foreach ([ImportStarted::class, ImportStepStarted::class, ImportStepFinished::class, ImportFinished::class] as $eventClass) {
        Event::listen($eventClass, function (object $event) use (&$fired) {
            $fired[] = [$event::class, $event->runId];
        });
    }

    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    expect(array_column($fired, 0))->toBe([ImportStarted::class, ImportStepStarted::class, ImportStepFinished::class, ImportFinished::class])
        ->and(array_unique(array_column($fired, 1)))->toHaveCount(1);
});

it('flags the import finished event when an entry fails to save', function () {
    Event::fake([ImportFinished::class]);
    Event::listen(ElementSaving::class, function (ElementSaving $event) {
        $event->isValid = false;
    });

    ($this->command)('entries-plain-text.json', ['--matchCriteria' => '={"title":"title"}'])
        ->assertSuccessful();

    expect(EntryElement::find()->section($this->section->handle)->count())->toBe(0);
    Event::assertDispatched(fn (ImportFinished $event) => $event->hasFailures);
});
