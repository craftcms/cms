<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Enums\TabType;
use CraftCms\Cms\FieldLayout\FieldLayout as FieldLayoutConfig;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Uri;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

beforeEach(function () {
    actingAs(User::findOne());

    $this->secondarySite = Site::factory()->create(['handle' => 'secondary', 'name' => 'Secondary']);

    $makeField = fn (string $name, string $handle, string $translationMethod) => Field::factory()->create([
        'name' => $name,
        'handle' => $handle,
        'type' => PlainText::class,
        'translationMethod' => $translationMethod,
    ]);
    $summaryField = $this->summaryField = $makeField('Summary', 'summary', 'site');
    $taglineField = $this->taglineField = $makeField('Tagline', 'tagline', 'site');
    $venueField = $this->venueField = $makeField('Venue', 'venue', 'none');
    Fields::refreshFields();

    $this->entryType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->state(function (array $attributes) use ($summaryField, $taglineField, $venueField) {
            $layout = FieldLayoutConfig::make($attributes['type']);
            $layout->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
                new CustomField(config: ['fieldUid' => $summaryField->uid]),
                new CustomField(config: ['fieldUid' => $taglineField->uid]),
            ));
            $layout->tab('Visit', function (FieldLayoutTab $tab) use ($venueField) {
                $tab->type = TabType::Sidebar;
                $tab->add(new CustomField(config: ['fieldUid' => $venueField->uid]));
            });

            return ['config' => $layout->getConfig()];
        }))
        ->create(['hasTitleField' => true]);
    EntryTypes::refreshEntryTypes();

    $this->section = Section::factory()
        ->withSites($this->secondarySite)
        ->withEntryTypes($this->entryType)
        ->create(['handle' => 'exhibitions', 'enableVersioning' => true]);
});

/** @param array<string, string> $values */
function comparedEntry(array $values): Entry
{
    $entry = EntryModel::factory()
        ->forSection(test()->section)
        ->forEntryType(test()->entryType)
        ->createElement(['title' => 'Light and Line']);
    $entry->setFieldValues($values);
    expect(Elements::saveElement($entry))->toBeTrue();

    return $entry;
}

/** @param array<string, int|string> $params */
function compareScreenUrl(Entry $entry, array $params): string
{
    $uri = Uri::of($entry->getCanonical(true)->getCpEditUrl());

    return $uri->withPath("{$uri->path()}/compare")->withQuery($params)->value();
}

/**
 * The status of each field in one side’s pane, by the field’s label.
 *
 * @return array<string, string|null>
 */
function paneStatuses(AssertableInertia $page, string $side): array
{
    return collect($page->toArray()['props'][$side]['ui']['nodes'])
        ->flatMap(fn (array $node) => $node['component'] === 'craft:tab' ? $node['children'] : [$node])
        ->mapWithKeys(fn (array $node) => [$node['props']['label'] => $node['props']['status'] ?? null])
        ->all();
}

/**
 * @param  array<int, array<string, mixed>>  $menu
 * @return array<string, mixed>
 */
function compareMenuLink(array $menu, string $label): array
{
    $link = collect($menu)->flatMap(fn (array $group) => $group['items'])->firstWhere('label', $label);
    expect($link)->not->toBeNull();

    return $link;
}

it('opens from a draft’s editor and compares the draft with the current entry', function () {
    $entry = comparedEntry(['summary' => 'Shown in the East Gallery', 'venue' => 'Main campus']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');
    $draft->setFieldValue('summary', 'Shown in the North Wing');
    expect(Elements::saveElement($draft))->toBeTrue();

    $compareUrl = null;
    get($draft->getCpEditUrl())
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use (&$compareUrl) {
            $compareUrl = $page->toArray()['props']['comparePageUrl'];
        });

    expect($compareUrl)->toBeString();
    app()->forgetScopedInstances();

    get($compareUrl)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Compare')
            ->where('from.label', 'Current')
            ->where('to.label', 'Extended run')
            ->where('from.ui.values', fn ($values) => collect($values)->flatten()->contains('Shown in the East Gallery'))
            ->where('to.ui.values', fn ($values) => collect($values)->flatten()->contains('Shown in the North Wing'))
            ->where('rows', fn ($rows) => collect($rows)->pluck('changed')->contains(true))
            ->where('to.editor.draftId', $draft->draftId)
            ->where('redirectUrl', fn (string $url) => str_contains(Crypt::decrypt($url), "/compare?draftId=$draft->draftId")))
        ->assertInertia(function (AssertableInertia $page) {
            foreach (['from', 'to'] as $side) {
                expect(paneStatuses($page, $side))->toMatchArray([
                    'Summary' => 'modified',
                    'Tagline' => null,
                    'Venue' => null,
                ]);
            }
        });
});

it('keeps each field in its tab, along with sidebar tabs, which have no sidebar to go in', function () {
    $entry = comparedEntry(['summary' => 'Shown in the East Gallery', 'venue' => 'East Gallery']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');

    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            foreach (['from', 'to'] as $side) {
                $tabs = collect($page->toArray()['props'][$side]['ui']['nodes'])->map(fn (array $node) => [
                    $node['props']['label'],
                    $node['props']['sidebar'] ?? false,
                    collect($node['children'])->pluck('props.label')->all(),
                ]);

                expect($tabs->all())->toBe([
                    ['Content', false, ['Summary', 'Tagline']],
                    ['Visit', true, ['Venue']],
                ]);
            }
        });
});

it('compares a revision with the revision before it', function () {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    $firstId = app(Revisions::class)->createRevision($entry, auth()->id());
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Opens in April');
    expect(Elements::saveElement($entry))->toBeTrue();
    $secondId = app(Revisions::class)->createRevision($entry, auth()->id());

    $first = Entry::find()->id($firstId)->revisions(null)->status(null)->one();
    $second = Entry::find()->id($secondId)->revisions(null)->status(null)->one();
    get(compareScreenUrl($entry, ['revisionId' => $second->revisionId]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.label', "Revision $first->revisionNum")
            ->where('to.label', "Revision $second->revisionNum")
            ->where('to.editor', null))
        ->assertInertia(fn (AssertableInertia $page) => expect(paneStatuses($page, 'to'))->toMatchArray([
            'Summary' => 'modified',
            'Venue' => null,
        ]));
});

it('compares two sites, flagging only translations that are missing on one side', function () {
    $entry = comparedEntry(['summary' => 'Twelve works on paper', 'tagline' => 'Drawing as light', 'venue' => 'Main campus']);
    $translation = Entry::find()->id($entry->id)->siteId($this->secondarySite->id)->status(null)->one();
    $translation->setFieldValues(['summary' => 'Douze œuvres sur papier', 'tagline' => '']);
    expect(Elements::saveElement($translation))->toBeTrue();

    get(compareScreenUrl($entry, ['siteId' => $translation->siteId, 'fromSiteId' => $entry->siteId]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.siteName', $entry->getSite()->getName())
            ->where('to.siteName', 'Secondary')
            ->where('to.editor.siteId', $translation->siteId))
        ->assertInertia(fn (AssertableInertia $page) => expect(paneStatuses($page, 'to'))->toMatchArray([
            'Summary' => null,
            'Tagline' => 'modified',
            'Venue' => null,
        ]));
});

it('links each version in one side’s menu to a comparison that keeps the other side', function () {
    $entry = comparedEntry(['summary' => 'Shown in the East Gallery']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');
    $revision = Entry::find()
        ->id(app(Revisions::class)->createRevision($entry, auth()->id()))
        ->revisions(null)
        ->status(null)
        ->one();

    $links = [];
    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use (&$links, $revision) {
            $props = $page->toArray()['props'];
            $links = [
                'fromDraft' => compareMenuLink($props['from']['versionMenu'], 'Extended run'),
                'fromCurrent' => compareMenuLink($props['from']['versionMenu'], 'Current'),
                'toRevision' => compareMenuLink($props['to']['versionMenu'], "Revision $revision->revisionNum")['href'],
            ];
        });

    expect($links['fromCurrent']['selected'])->toBeTrue()
        ->and($links['fromDraft']['selected'])->toBeFalse();

    app()->forgetScopedInstances();
    get($links['toRevision'])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.label', 'Current')
            ->where('to.label', "Revision $revision->revisionNum"));

    app()->forgetScopedInstances();
    get($links['fromDraft']['href'])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.label', 'Extended run')
            ->where('to.label', 'Extended run'));
});

it('returns 404 when the version to compare with belongs to another entry', function () {
    $entry = comparedEntry(['summary' => 'Ours']);
    $other = comparedEntry(['summary' => 'Theirs']);
    $otherDraft = app(Drafts::class)->createDraft($other, auth()->id());

    get(compareScreenUrl($entry, ['from' => "draft-$otherDraft->draftId"]))->assertNotFound();
});
