<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Conditions\TextFieldConditionRule;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\Table;
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
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Uri;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travel;

beforeEach(function () {
    actingAs(User::findOne());

    $this->secondarySite = Site::factory()->create(['handle' => 'secondary', 'name' => 'Secondary', 'language' => 'fr-FR']);

    $makeField = fn (string $name, string $handle, string $translationMethod) => Field::factory()->create([
        'name' => $name,
        'handle' => $handle,
        'type' => PlainText::class,
        'translationMethod' => $translationMethod,
    ]);
    $summaryField = $this->summaryField = $makeField('Summary', 'summary', 'site');
    $taglineField = $this->taglineField = $makeField('Tagline', 'tagline', 'site');
    $venueField = $this->venueField = $makeField('Venue', 'venue', 'none');
    $programmeField = Field::factory()->create([
        'name' => 'Programme',
        'handle' => 'programme',
        'type' => Table::class,
        'settings' => ['columns' => [
            'col1' => ['heading' => 'Date', 'handle' => 'date', 'type' => 'date'],
            'col2' => ['heading' => 'Link', 'handle' => 'link', 'type' => 'link', 'types' => ['url'], 'showLabelField' => true],
        ]],
    ]);
    Fields::refreshFields();

    $this->entryType = EntryType::factory()
        ->withFieldLayout(FieldLayout::factory()->state(function (array $attributes) use ($summaryField, $taglineField, $venueField, $programmeField) {
            $layout = FieldLayoutConfig::make($attributes['type']);
            $layout->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(
                new CustomField(config: ['fieldUid' => $summaryField->uid]),
                new CustomField(config: ['fieldUid' => $taglineField->uid]),
                new CustomField(config: ['fieldUid' => $programmeField->uid]),
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
 * The field nodes in one side’s pane, in order, out of their tabs.
 *
 * @return list<array<string, mixed>>
 */
function paneFields(AssertableInertia $page, string $side): array
{
    return collect($page->toArray()['props'][$side]['ui']['nodes'])
        ->flatMap(fn (array $node) => $node['component'] === 'craft:tab' ? $node['children'] : [$node])
        ->all();
}

/**
 * Whether each field on one side changed, by the field’s label.
 *
 * @return array<string, bool|null>
 */
function changedFields(AssertableInertia $page, string $side): array
{
    $changed = collect($page->toArray()['props']['rows'])->pluck('changed', "{$side}Uid");

    return collect(paneFields($page, $side))
        ->mapWithKeys(fn (array $node) => [$node['props']['label'] => $changed[$node['props']['layoutUid'] ?? $node['uid']] ?? null])
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

it('compares a draft with the current entry by default', function () {
    $entry = comparedEntry(['summary' => 'Shown in the East Gallery', 'venue' => 'Main campus']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');
    $draft->setFieldValue('summary', 'Shown in the North Wing');
    expect(Elements::saveElement($draft))->toBeTrue();

    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
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
                expect(changedFields($page, $side))->toMatchArray([
                    'Summary' => true,
                    'Tagline' => false,
                    'Venue' => false,
                ])
                    ->and(collect(paneFields($page, $side))->pluck('props.status')->filter()->all())->toBe([])
                    ->and(collect(paneFields($page, $side))->pluck('props.labelBadge', 'props.label')->filter()->all())
                    ->toBe($side === 'to' ? ['Summary' => 'Changed'] : []);
            }
        });
});

it('links each version in the editor’s Versions panel to its default comparison, whichever is open', function (string $open) {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    app(Revisions::class)->createRevision($entry, auth()->id());
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Opens in April');
    expect(Elements::saveElement($entry))->toBeTrue();
    // The newest revision is the current entry's, so the panel lists the two before it.
    [$first, $second] = collect(Entry::find()->revisionOf($entry)->status(null)->all())->sortBy('revisionNum')->values()->all();
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');

    $links = [];
    $opened = match ($open) {
        'current' => $entry,
        'draft' => $draft,
        'revision' => $second,
    };
    get($opened->getCpEditUrl())
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use (&$links) {
            $links = collect($page->toArray()['props']['contextMenu']['items'])
                ->where('type', 'link')
                ->filter(fn (array $item) => $item['label'] !== 'View all revisions')
                ->mapWithKeys(fn (array $item) => [$item['label'] => $item['compareHref'] ?? null])
                ->all();
        });

    expect($links)->toBe([
        // Compared with the revision before its own.
        'Current' => compareScreenUrl($entry, []),
        // Compared with the current entry.
        'Extended run' => compareScreenUrl($entry, ['draftId' => $draft->draftId]),
        // Compared with the revision before it.
        "Revision $second->revisionNum" => compareScreenUrl($entry, ['revisionId' => $second->revisionId]),
        // The first one recorded has nothing before it.
        "Revision $first->revisionNum" => null,
    ]);
})->with(['current', 'draft', 'revision']);

it('leads the editor’s action menu with a link comparing the version being viewed with its base', function (string $open, bool $linked) {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Opens in April');
    expect(Elements::saveElement($entry))->toBeTrue();
    [$first, $second] = collect(Entry::find()->revisionOf($entry)->status(null)->all())->sortBy('revisionNum')->values()->all();

    [$opened, $compareUrl] = match ($open) {
        'current' => [$entry, compareScreenUrl($entry, [])],
        // Saved once, so its own revision is its only one.
        'new entry' => [EntryModel::factory()->forSection(test()->section)->forEntryType(test()->entryType)->createElement(['title' => 'Just made']), null],
        'unsaved edits' => [app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true), compareScreenUrl($entry, [])],
        'draft' => [$draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run'), compareScreenUrl($entry, ['draftId' => $draft->draftId])],
        'revision' => [$second, compareScreenUrl($entry, ['revisionId' => $second->revisionId])],
        'first revision' => [$first, null],
    };

    $lead = null;
    get($open === 'unsaved edits' ? $entry->getCpEditUrl() : $opened->getCpEditUrl())
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use (&$lead) {
            $lead = $page->toArray()['props']['actionMenu'][0] ?? null;
        });

    if ($linked) {
        expect($lead)->toMatchArray([
            'label' => 'Compare changes',
            'behavior' => ['type' => 'link', 'href' => $compareUrl],
        ]);
    } else {
        expect($lead['label'] ?? null)->not->toBe('Compare changes');
    }
})->with([
    'for the current entry' => ['current', true],
    'not for an entry with no revision before its own' => ['new entry', false],
    'for the user’s unsaved edits' => ['unsaved edits', true],
    'for a draft' => ['draft', true],
    'for a revision' => ['revision', true],
    'not for the first revision, with nothing before it' => ['first revision', false],
]);

it('shows the user’s unsaved edits as the current entry, compared with it as it’s saved', function () {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    $edits = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
    $edits->setFieldValue('summary', 'Opens in May');
    expect(Elements::saveElement($edits))->toBeTrue();

    get(compareScreenUrl($entry, []))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.label', 'Current')
            ->where('from.ui.values', fn ($values) => collect($values)->flatten()->contains('Opens in March'))
            ->where('to.ui.values', fn ($values) => collect($values)->flatten()->contains('Opens in May'))
            ->where('to.editor.isProvisionalDraft', true)
            ->where('badged', ['from' => false, 'to' => true]));
});

it('lists the user’s unsaved edits beside the current entry as saved, and selects whichever each side shows', function () {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Opens in April');
    expect(Elements::saveElement($entry))->toBeTrue();
    $edits = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
    $edits->setFieldValue('summary', 'Opens in May');
    expect(Elements::saveElement($edits))->toBeTrue();

    /** @return list<array{string, string|null, bool, bool}> Each of the current group's items: its label, badge, and whether it's selected or disabled. */
    $current = fn (array $menu): array => collect($menu[0]['items'])
        ->map(fn (array $item): array => [$item['label'], $item['badge']['label'] ?? null, $item['selected'] ?? false, $item['disabled'] ?? false])
        ->all();

    // By default, the edits, compared with the entry as it's saved.
    get(compareScreenUrl($entry, []))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($current) {
            $props = $page->toArray()['props'];

            // Each side can't choose what the other shows.
            expect($current($props['to']['versionMenu']))->toBe([['Current', null, false, true], ['Current', 'Edited', true, false]])
                ->and($current($props['from']['versionMenu']))->toBe([['Current', null, true, false], ['Current', 'Edited', false, true]])
                ->and([$props['from']['edited'], $props['to']['edited']])->toBe([false, true])
                ->and($props['to']['editor']['isProvisionalDraft'])->toBeTrue();
        });
    app()->forgetScopedInstances();

    // Asked for as it's saved, the entry isn't editable here, as saving would go into the edits.
    get(compareScreenUrl($entry, ['edits' => 0, 'from' => 'edits']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('to.edited', false)
            ->where('to.editor', null)
            ->where('from.edited', true)
            ->where('to.ui.values', fn ($values) => collect($values)->flatten()->contains('Opens in April'))
            ->where('from.ui.values', fn ($values) => collect($values)->flatten()->contains('Opens in May')));
});

it('lists only the current entry when the user has no unsaved edits to it', function () {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');

    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.versionMenu.0.items', fn ($items) => collect($items)->pluck('label')->all() === ['Current'])
            ->where('from.edited', false));
});

it('badges changes on the side that made them, or on both when neither did', function (string $from, string $to, array $badged) {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    $first = Entry::find()->revisionOf($entry)->status(null)->one();
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Opens in April');
    expect(Elements::saveElement($entry))->toBeTrue();
    $second = collect(Entry::find()->revisionOf($entry)->status(null)->all())->sortByDesc('revisionNum')->first();
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');
    $draft->setFieldValue('summary', 'Opens in May');
    expect(Elements::saveElement($draft))->toBeTrue();
    $otherDraft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Short run');

    $versions = [
        'current' => [[], 'current'],
        'draft' => [['draftId' => $draft->draftId], "draft-$draft->draftId"],
        'other draft' => [['draftId' => $otherDraft->draftId], "draft-$otherDraft->draftId"],
        'older revision' => [['revisionId' => $first->revisionId], "revision-$first->revisionId"],
        'newer revision' => [['revisionId' => $second->revisionId], "revision-$second->revisionId"],
    ];

    get(compareScreenUrl($entry, [...$versions[$to][0], 'from' => $versions[$from][1]]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('badged', $badged)
            ->where('rows', fn ($rows) => collect($rows)->pluck('changed')->contains(true)))
        ->assertInertia(function (AssertableInertia $page) use ($badged) {
            foreach (['from', 'to'] as $side) {
                expect(collect(paneFields($page, $side))->pluck('props.labelBadge')->filter()->isNotEmpty())->toBe($badged[$side]);
            }
        });
})->with([
    'the newer of two revisions' => ['older revision', 'newer revision', ['from' => false, 'to' => true]],
    'the newer of two revisions, on the left' => ['newer revision', 'older revision', ['from' => true, 'to' => false]],
    'the current entry over a revision' => ['current', 'older revision', ['from' => true, 'to' => false]],
    'a draft over the current entry' => ['current', 'draft', ['from' => false, 'to' => true]],
    'a draft over a revision, on the left' => ['draft', 'older revision', ['from' => true, 'to' => false]],
    'both of two drafts' => ['other draft', 'draft', ['from' => true, 'to' => true]],
]);

it('doesn’t flag a table with date and link columns that both versions hold the same rows in', function () {
    $entry = comparedEntry(['summary' => 'Shown in the East Gallery']);
    $entry->setFieldValue('programme', [
        ['col1' => '2026-10-24', 'col2' => ['type' => 'url', 'value' => 'https://example.com/tickets', 'label' => 'Book tickets']],
    ]);
    expect(Elements::saveElement($entry))->toBeTrue();
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');
    $draft->setFieldValue('summary', 'Shown in the North Wing');
    expect(Elements::saveElement($draft))->toBeTrue();

    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            foreach (['from', 'to'] as $side) {
                expect(changedFields($page, $side))->toMatchArray([
                    'Summary' => true,
                    'Programme' => false,
                ]);
            }
        });
});

it('keeps a field only the older version shows in its place among the rows', function () {
    $layout = FieldLayout::query()->findOrFail($this->entryType->fieldLayoutId);
    $config = $layout->config;
    $config['tabs'][0]['elements'][1]['elementCondition'] = [
        'class' => EntryCondition::class,
        'elementType' => Entry::class,
        'conditionRules' => [[
            'class' => TextFieldConditionRule::class,
            'uid' => Str::uuid()->toString(),
            'fieldUid' => $this->summaryField->uid,
            'layoutElementUid' => $config['tabs'][0]['elements'][0]['uid'],
            'operator' => 'notempty',
        ]],
    ];
    $layout->update(['config' => $config]);
    Fields::refreshFields();
    EntryTypes::refreshEntryTypes();

    $entry = comparedEntry(['summary' => 'Shown in the East Gallery', 'tagline' => 'Drawing as light']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'No summary');
    $draft->setFieldValue('summary', '');
    expect(Elements::saveElement($draft))->toBeTrue();

    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) {
            $labels = collect([...paneFields($page, 'from'), ...paneFields($page, 'to')])
                ->mapWithKeys(fn (array $node) => [$node['props']['layoutUid'] ?? $node['uid'] => $node['props']['label']]);
            $rows = collect($page->toArray()['props']['rows'])
                ->map(fn (array $row) => [$labels[$row['fromUid'] ?? $row['toUid']], $row['toUid'] !== null]);

            expect($rows->all())->toBe([
                ['Summary', true],
                ['Tagline', false],
                ['Programme', true],
                ['Venue', true],
            ]);
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
                    ['Content', false, ['Summary', 'Tagline', 'Programme']],
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
        ->assertInertia(fn (AssertableInertia $page) => expect(changedFields($page, 'to'))->toMatchArray([
            'Summary' => true,
            'Venue' => false,
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
        ->assertInertia(fn (AssertableInertia $page) => expect(changedFields($page, 'to'))->toMatchArray([
            'Summary' => false,
            'Tagline' => true,
            'Venue' => false,
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
                'fromRevision' => compareMenuLink($props['from']['versionMenu'], "Revision $revision->revisionNum"),
                'fromCurrent' => compareMenuLink($props['from']['versionMenu'], 'Current'),
                'toRevision' => compareMenuLink($props['to']['versionMenu'], "Revision $revision->revisionNum")['href'],
            ];
        });

    expect($links['fromCurrent']['selected'])->toBeTrue()
        ->and($links['fromRevision']['selected'])->toBeFalse();

    app()->forgetScopedInstances();
    get($links['toRevision'])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.label', 'Current')
            ->where('to.label', "Revision $revision->revisionNum"));

    app()->forgetScopedInstances();
    get($links['fromRevision']['href'])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('from.label', "Revision $revision->revisionNum")
            ->where('to.label', 'Extended run'));
});

it('disables the choices that would show the same site and version on both sides', function () {
    $entry = comparedEntry(['summary' => 'Shown in the East Gallery']);
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), 'Extended run');
    $disabled = fn (array $menu): array => collect($menu)
        ->flatMap(fn (array $item) => $item['items'] ?? [$item])
        ->filter(fn (array $item) => $item['disabled'] ?? false)
        ->map(fn (array $item) => [$item['type'], $item['label'], isset($item['href'])])
        ->values()
        ->all();

    // The same site: each side’s version menu disables the other side’s version.
    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($disabled) {
            $props = $page->toArray()['props'];

            expect($disabled($props['from']['versionMenu']))->toBe([['button', 'Extended run', false]])
                ->and($disabled($props['to']['versionMenu']))->toBe([['button', 'Current', false]])
                ->and($disabled($props['from']['siteMenu']))->toBe([])
                ->and($disabled($props['to']['siteMenu']))->toBe([]);
        });

    // The same version: each side’s site menu disables the other side’s site.
    app()->forgetScopedInstances();
    get(compareScreenUrl($entry, ['siteId' => $this->secondarySite->id, 'fromSiteId' => $entry->siteId]))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($disabled, $entry) {
            $props = $page->toArray()['props'];

            expect($disabled($props['from']['siteMenu']))->toBe([['button', 'Secondary', false]])
                ->and($disabled($props['to']['siteMenu']))->toBe([['button', $entry->getSite()->getName(), false]])
                ->and($disabled($props['from']['versionMenu']))->toBe([])
                ->and($disabled($props['to']['versionMenu']))->toBe([]);
        });
});

it('returns 404 when the version to compare with belongs to another entry', function () {
    $entry = comparedEntry(['summary' => 'Ours']);
    $other = comparedEntry(['summary' => 'Theirs']);
    $otherDraft = app(Drafts::class)->createDraft($other, auth()->id());

    get(compareScreenUrl($entry, ['from' => "draft-$otherDraft->draftId"]))->assertNotFound();
});
