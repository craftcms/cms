<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Models\User;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Support\ComputeUrl;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());

    $related = Field::factory()->create(['name' => 'Related', 'handle' => 'related', 'type' => Entries::class]);
    Fields::refreshFields();

    $type = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->withContentTab([
            new EntryTitleField,
            new CustomField(config: ['fieldUid' => $related->uid]),
        ]))
        ->create(['name' => 'Article', 'handle' => 'article']);
    EntryTypes::refreshEntryTypes();

    $section = Section::factory()->withEntryTypes($type)->create(['name' => 'Articles', 'handle' => 'articles']);

    $this->entries = collect(['Alpha', 'Bravo', 'Charlie', 'Home'])->mapWithKeys(fn (string $title) => [
        $title => Entry::factory()->forSection($section)->forEntryType($type)->indexed()->createElement(['title' => $title]),
    ]);
});

/**
 * Opens a control panel page in another tab of the same browser as `$page`, so the two can broadcast to each other.
 */
function openCpTab(AwaitableWebpage $page, string $path): AwaitableWebpage
{
    $url = ComputeUrl::from('/'.trim((string) Cms::config()->cpTrigger, '/').'/'.ltrim($path, '/'));

    return new AwaitableWebpage($page->page()->context()->newPage()->goto($url), $url)->assertPresent('main[id]');
}

/**
 * @return list<string>
 */
function relatedTitles(EntryElement $entry): array
{
    return EntryElement::find()->id($entry->id)->one()->getFieldValue('related')->collect()->pluck('title')->all();
}

it('relates entries chosen in the element selector', function () {
    $home = $this->entries['Home'];

    visitCp("content/entries/articles/$home->id")
        ->click('#main >> role=button[name="Add an entry"]')
        ->click('craft-element-selector-modal >> role=checkbox[name="Select Bravo"]')
        ->click('craft-element-selector-modal >> role=checkbox[name="Select Alpha"]')
        ->click('craft-element-selector-modal >> role=button[name="Add an entry"]')
        ->assertMissing('craft-element-selector-modal dialog[open]')
        ->assertVisible('role=group[name="Related"] >> role=checkbox[name="Select Alpha"]')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    expect(relatedTitles($home))->toBe(['Alpha', 'Bravo']);
});

it('reorders and removes related entries', function () {
    $home = $this->entries['Home'];
    $home->setFieldValue('related', $this->entries->only(['Alpha', 'Bravo', 'Charlie'])->pluck('id')->all());
    Elements::saveElement($home);

    visitCp("content/entries/articles/$home->id")
        ->click('role=group[name="Related"] >> craft-chip:has-text("Alpha") >> role=button[name="Reorder"]')
        ->click('role=button[name="Move down"]')
        ->assertSeeIn('role=group[name="Related"] >> craft-chip >> nth=0', 'Bravo')
        ->assertMissing('role=button[name="Move down"]')
        ->click('role=group[name="Related"] >> craft-chip:has-text("Charlie") >> role=button[name="Actions"]')
        ->click('role=button[name="Remove"]')
        ->assertMissing('role=group[name="Related"] >> role=checkbox[name="Select Charlie"]')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    expect(relatedTitles($home))->toBe(['Bravo', 'Alpha']);
});

it('updates a related entry’s chip when the entry is saved in a slideout', function () {
    $home = $this->entries['Home'];
    $home->setFieldValue('related', [$this->entries['Alpha']->id]);
    Elements::saveElement($home);

    visitCp("content/entries/articles/$home->id")
        ->click('role=group[name="Related"] >> craft-chip:has-text("Alpha") >> role=button[name="Actions"]')
        ->click('role=button[name="Edit entry"]')
        ->type('role=dialog >> input[name="title"]', 'Alpha Prime')
        ->click('role=dialog >> role=button[name="Save"]')
        ->assertMissing('role=dialog')
        ->assertSeeIn('role=group[name="Related"] >> craft-chip', 'Alpha Prime');
});

it('refreshes a server-rendered chip in place when its entry is saved', function () {
    $alpha = $this->entries['Alpha'];
    $chipHtml = app(ElementHtml::class)->elementChipHtml($alpha, [
        'context' => 'field',
        'hyperlink' => true,
        'inputName' => 'related[]',
        'showActionMenu' => true,
    ]);

    $page = visitCp('dashboard');
    $page->script(sprintf(<<<'JS'
        (() => {
            const host = document.createElement('div');
            host.id = 'related';
            host.innerHTML = %s;
            document.querySelector('#main').prepend(host);
            host.querySelector('craft-chip').selected = true;
        })()
        JS, json_encode($chipHtml)));
    $page->script("document.querySelector('#related craft-chip a').focus()");
    $page->assertScript("document.activeElement === document.querySelector('#related craft-chip a')", true);

    $alpha->title = 'Alpha Prime';
    Elements::saveElement($alpha);
    $page->script(sprintf("window.dispatchEvent(new CustomEvent('craft:broadcast', {detail: {event: 'saveElement', id: %d}}))", $alpha->id));

    $page->assertSeeIn('#related craft-chip', 'Alpha Prime')
        ->assertScript(<<<'JS'
            (() => {
                const chip = document.querySelector('#related craft-chip');
                return {
                    selected: chip.selected,
                    input: chip.querySelector(':scope > input[name="related[]"]')?.value,
                    actions: chip.querySelectorAll('[slot="suffix"] craft-action-menu').length,
                    focused: document.activeElement === chip.querySelector('a'),
                };
            })()
            JS, ['selected' => true, 'input' => (string) $alpha->id, 'actions' => 1, 'focused' => true]);
});

it('updates a related entry’s chip in another tab when the entry is saved there', function () {
    $home = $this->entries['Home'];
    $alpha = $this->entries['Alpha'];
    $home->setFieldValue('related', [$alpha->id]);
    Elements::saveElement($home);

    $homeTab = visitCp("content/entries/articles/$home->id")
        ->assertSeeIn('role=group[name="Related"] >> craft-chip', 'Alpha');

    openCpTab($homeTab, "content/entries/articles/$alpha->id")
        ->type('#main >> input[name="title"]', 'Alpha Prime')
        ->click('#main >> role=button[name="Save"]')
        ->assertSee('Entry saved.');

    $homeTab->assertSeeIn('role=group[name="Related"] >> craft-chip', 'Alpha Prime');
});
