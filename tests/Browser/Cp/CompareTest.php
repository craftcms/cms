<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Drafts;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $summary = Field::factory()->create(['name' => 'Summary', 'handle' => 'summary', 'type' => PlainText::class]);
    $programme = Field::factory()->create([
        'name' => 'Programme',
        'handle' => 'programme',
        'type' => Table::class,
        'settings' => ['columns' => [
            'col1' => ['heading' => 'Event', 'handle' => 'event', 'type' => 'singleline'],
            'col2' => ['heading' => 'Date', 'handle' => 'date', 'type' => 'date'],
        ]],
    ]);
    Fields::refreshFields();

    $type = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new EntryTitleField,
            new CustomField(config: ['fieldUid' => $summary->uid]),
            new CustomField(config: ['fieldUid' => $programme->uid]),
        ]))
        ->create(['name' => 'Exhibition', 'handle' => 'exhibition']);
    $this->entryType = $type;
    EntryTypes::refreshEntryTypes();

    $section = $this->section = Section::factory()
        ->withEntryTypes($type)
        ->create(['name' => 'Exhibitions', 'handle' => 'exhibitions', 'enableVersioning' => true]);

    $this->entry = Entry::factory()->forSection($section)->forEntryType($type)
        ->createElement(['title' => 'Light and Line']);
    $this->entry->setFieldValues([
        'summary' => 'Opens in March',
        'programme' => [['col1' => 'Opening talk', 'col2' => '2026-10-24']],
    ]);
    expect(Elements::saveElement($this->entry))->toBeTrue();
});

/**
 * Creates a draft of the entry with the given summary.
 */
function compareDraft(string $summary): EntryElement
{
    $draft = Drafts::createDraft(test()->entry, auth()->id(), 'Extended run');
    $draft->setFieldValue('summary', $summary);
    expect(Elements::saveElement($draft))->toBeTrue();

    return $draft;
}

/**
 * Opens the Compare screen of the draft with the entry as it's saved.
 */
function visitCompare(EntryElement $draft): AwaitableWebpage
{
    return visit(route('craft.cp.elements.compare', ['id' => test()->entry->id, 'draftId' => $draft->draftId], false))->assertPresent('main[id]');
}

/**
 * What `$read` returns, once it's what's expected, or as it last was after a few seconds, since autosave saves an edit
 * a moment after it's made. The page waits in between, which lets the app serve the autosave request.
 *
 * @param  Closure(): mixed  $read
 */
function eventually(AwaitableWebpage $page, Closure $read, mixed $expected): mixed
{
    $deadline = microtime(true) + 5;

    do {
        $value = $read();

        if ($value === $expected) {
            break;
        }

        $page->wait(0.2);
    } while (microtime(true) < $deadline);

    return $value;
}

function savedDraft(EntryElement $draft): ?EntryElement
{
    return EntryElement::find()->drafts()->provisionalDrafts(null)->draftId($draft->draftId)->status(null)->one();
}

it('compares a draft with the current entry from the Versions panel', function () {
    $draft = compareDraft('Opens in May');

    $page = visit(parse_url($this->entry->getCpEditUrl(), PHP_URL_PATH)."?draftId=$draft->draftId")
        ->assertPresent('main[id]')
        ->click('role=button[name="Versions"]')
        ->click('role=link[name="Compare changes Extended run"]')
        ->assertSee('Compare “Light and Line”')
        ->assertValue('role=group[name=/compared with/i] >> role=textbox[name=/^Summary/]', 'Opens in March')
        ->assertValue('role=group[name=/showing/i] >> role=textbox[name=/^Summary/]', 'Opens in May');

    assertNoJavaScriptErrors($page);
});

it('copies a field’s value from the older version, and undoes it', function () {
    $draft = compareDraft('Opens in May');

    $page = visitCompare($draft)
        ->click('role=button[name="Copy Summary from Current"]')
        ->assertSee('Field value copied.')
        ->assertValue('role=group[name=/showing/i] >> role=textbox[name=/^Summary/]', 'Opens in March')
        ->assertMissing('role=button[name="Copy Summary from Current"]');

    expect(eventually($page, fn () => savedDraft($draft)?->getFieldValue('summary'), 'Opens in March'))->toBe('Opens in March');

    $page->click('role=button[name="Undo"]')
        ->assertValue('role=group[name=/showing/i] >> role=textbox[name=/^Summary/]', 'Opens in May')
        ->assertVisible('role=button[name="Copy Summary from Current"]')
        ->assertDontSee('Field value copied.');

    expect(eventually($page, fn () => savedDraft($draft)?->getFieldValue('summary'), 'Opens in May'))->toBe('Opens in May');

    assertNoJavaScriptErrors($page);
});

it('offers no copy once a table is edited back to the older version’s value, after its edit autosaved', function () {
    // The user's unsaved edits, compared with the current entry, which they don't differ from yet.
    $draft = Drafts::createDraft($this->entry, auth()->id(), provisional: true);
    $cell = 'role=group[name=/showing/i] >> role=group[name=/^Programme/] >> role=textbox[name=/Event/]';
    $copy = 'role=button[name="Copy Programme from A"]';

    $page = visit(route('craft.cp.elements.compare', ['id' => $this->entry->id], false))
        ->assertPresent('main[id]')
        ->assertValue($cell, 'Opening talk')
        ->assertMissing($copy)
        ->append($cell, '!')
        ->assertVisible($copy);

    expect(eventually($page, fn () => savedDraft($draft)?->getFieldValue('programme')[0]['col1'] ?? null, 'Opening talk!'))->toBe('Opening talk!');

    $page->keys($cell, ['Backspace'])
        ->assertValue($cell, 'Opening talk')
        ->assertDontSee('Changed')
        ->assertMissing($copy);

    assertNoJavaScriptErrors($page);
});

it('copies a field’s value from the newer version into the older one, which is edited too', function () {
    $draft = compareDraft('Opens in May');

    $page = visitCompare($draft)
        ->click('role=button[name="Copy Summary from Extended run"]')
        ->assertSee('Field value copied.')
        ->assertValue('role=group[name=/compared with/i] >> role=textbox[name=/^Summary/]', 'Opens in May')
        ->assertMissing('role=button[name="Copy Summary from Extended run"]')
        ->assertMissing('role=button[name="Copy Summary from Current"]');

    $edits = fn () => EntryElement::find()->provisionalDrafts()->draftOf($this->entry)->status(null)->one()?->getFieldValue('summary');

    expect(eventually($page, $edits, 'Opens in May'))->toBe('Opens in May');

    assertNoJavaScriptErrors($page);
});

it('compares two entries chosen in their index', function () {
    $other = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'Shadow and Form']);
    $other->setFieldValue('summary', 'Opens in June');
    expect(Elements::saveElement($other))->toBeTrue();

    $page = visitCp('content/entries/exhibitions')
        ->click('#main >> role=checkbox[name="Select Light and Line"]')
        ->click('#main >> role=button[name="Actions"] >> nth=-1')
        ->assertMissing('role=link[name="Compare"]')
        ->click('#main >> role=checkbox[name="Select Shadow and Form"]')
        ->click('#main >> role=button[name="Actions"] >> nth=-1')
        ->click('role=link[name="Compare"]')
        ->assertSee('Compare Light and Line with Shadow and Form')
        ->assertValue('role=group[name=/compared with/i] >> role=textbox[name=/^Summary/]', 'Opens in March')
        ->assertValue('role=group[name=/showing/i] >> role=textbox[name=/^Summary/]', 'Opens in June')
        ->assertVisible('role=button[name="Copy Summary from Light and Line"]')
        ->assertVisible('role=button[name="Copy Summary from Shadow and Form"]');

    assertNoJavaScriptErrors($page);
});

it('names the sides by their letters when two entries share a title', function () {
    $other = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'Light and Line']);
    $other->setFieldValue('summary', 'Opens in June');
    expect(Elements::saveElement($other))->toBeTrue();

    $page = visit(route('craft.cp.elements.compare-elements', ['fromId' => $this->entry->id, 'toId' => $other->id], false))
        ->assertPresent('main[id]')
        ->assertPresent('role=heading[name=/^A, compared with: Light and Line/]')
        ->assertPresent('role=heading[name=/^B, showing: Light and Line/]')
        ->assertVisible('role=button[name="Copy Summary from A"]')
        ->assertVisible('role=button[name="Copy Summary from B"]');

    assertNoJavaScriptErrors($page);
});

it('saves the side a value was copied into with Cmd/Ctrl+S', function () {
    $other = Entry::factory()->forSection($this->section)->forEntryType($this->entryType)
        ->createElement(['title' => 'Shadow and Form']);
    $other->setFieldValue('summary', 'Opens in June');
    expect(Elements::saveElement($other))->toBeTrue();

    $page = visit(route('craft.cp.elements.compare-elements', ['fromId' => $this->entry->id, 'toId' => $other->id], false))
        ->assertPresent('main[id]')
        ->click('role=button[name="Copy Summary from Shadow and Form"]')
        ->assertSee('Field value copied.')
        ->keys('main[id]', ['Control+s']);

    $saved = fn () => EntryElement::find()->id($this->entry->id)->status(null)->one()?->getFieldValue('summary');

    expect(eventually($page, $saved, 'Opens in June'))->toBe('Opens in June');

    assertNoJavaScriptErrors($page);
});
