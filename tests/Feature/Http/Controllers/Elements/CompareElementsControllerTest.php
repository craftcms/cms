<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
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
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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
    $canonical = $entry->getCanonical(true);

    // As the screen’s requests build it.
    url()->forceScheme('https');

    return Url::urlWithParams(
        route('craft.cp.elements.compare', ['id' => $canonical->id]),
        array_replace([...$params, 'siteId' => $canonical->siteId], $params),
    );
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
            ->where('redirectUrl', fn (string $url) => str_contains(Crypt::decrypt($url), "/compare/$entry->id?draftId=$draft->draftId")))
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

it('lists the current entry’s own revision only when it’s being compared', function () {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    $firstId = app(Revisions::class)->createRevision($entry, auth()->id());
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Opens in April');
    expect(Elements::saveElement($entry))->toBeTrue();
    $currentId = app(Revisions::class)->createRevision($entry, auth()->id());

    $first = Entry::find()->id($firstId)->revisions(null)->status(null)->one();
    $current = Entry::find()->id($currentId)->revisions(null)->status(null)->one();
    $revisionLabels = fn (array $menu): array => collect($menu)
        ->firstWhere('heading', 'Revisions')['items'] ?? [];

    get(compareScreenUrl($entry, []))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => expect(
            collect($revisionLabels($page->toArray()['props']['from']['versionMenu']))->pluck('label')->all()
        )->toContain("Revision $first->revisionNum")->not->toContain("Revision $current->revisionNum"));

    app()->forgetScopedInstances();
    get(compareScreenUrl($entry, ['from' => "revision-$current->revisionId"]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => expect(
            collect($revisionLabels($page->toArray()['props']['from']['versionMenu']))->pluck('label')->all()
        )->toContain("Revision $current->revisionNum"));
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
    // A newer revision, which is the current entry's, so the one above is listed.
    travel(1)->minutes();
    $entry->setFieldValue('summary', 'Shown in the North Wing');
    expect(Elements::saveElement($entry))->toBeTrue();
    app(Revisions::class)->createRevision($entry, auth()->id());

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

it('forbids comparing a draft with a current entry the user can’t view', function () {
    $entry = comparedEntry(['summary' => 'Someone else’s']);
    $user = UserModel::factory()
        ->withPermissions([
            'accessCp',
            sprintf('editSite:%s', Site::query()->where('primary', true)->value('uid')),
            "viewEntries:{$this->section->uid}",
        ])
        ->createElement(['admin' => false]);
    $draft = app(Drafts::class)->createDraft($entry, $user->id);

    actingAs($user);

    get(compareScreenUrl($entry, ['draftId' => $draft->draftId]))->assertForbidden();
});

it('links each draft in the Versions panel to a comparison without looking up the current entry for each', function () {
    $entry = comparedEntry(['summary' => 'Opens in March']);
    app(Drafts::class)->createDraft($entry, auth()->id(), 'First');
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });
    $queriesForEditScreen = function () use ($entry, &$queries): int {
        get($entry->getCpEditUrl())->assertOk();
        $queries = 0;
        get($entry->getCpEditUrl())->assertOk();

        return $queries;
    };

    $withOneDraft = $queriesForEditScreen();
    foreach (range(1, 3) as $i) {
        app(Drafts::class)->createDraft($entry, auth()->id(), "Draft $i");
    }

    expect($queriesForEditScreen())->toBe($withOneDraft);
});

function comparedAsset(): Asset
{
    config()->set('filesystems.disks.compare-test', ['driver' => 'local', 'root' => storage_path('framework/testing/compare')]);
    $volume = Volume::factory()->create(['fs' => 'compare-test', 'propagationMethod' => PropagationMethod::All->value]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
        'filename' => 'light.txt',
        'kind' => 'text',
    ]);
    $asset->title = 'Light';
    expect(Elements::saveElement($asset))->toBeTrue();
    $translation = Asset::find()->id($asset->id)->siteId(test()->secondarySite->id)->one();
    $translation->title = 'Lumière';
    expect(Elements::saveElement($translation))->toBeTrue();

    return $translation;

}

it('links an asset’s other sites to a comparison from its edit screen', function () {
    $asset = comparedAsset();

    get(Url::urlWithParams($asset->getCpEditUrl(), ['siteId' => $asset->siteId]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('sidebarUi.nodes', function (Collection $nodes) use ($asset) {
            $sites = collect($nodes->first(fn (array $node) => ($node['control']['component'] ?? null) === 'craft:element-sites')['control']['props']['sites'])
                ->pluck('compareUrl', 'id');

            return $sites[$asset->siteId] === null &&
                str_ends_with($sites[Sites::getPrimarySite()->id], sprintf('/compare/%s?fromSiteId=%s&siteId=%s', $asset->id, Sites::getPrimarySite()->id, $asset->siteId));
        }));
});

it('compares an asset in two sites, with site menus but no version menus, as assets have no drafts or revisions', function () {
    $asset = comparedAsset();
    $primary = Sites::getPrimarySite();

    get(route('craft.cp.elements.compare', ['id' => $asset->id, 'fromSiteId' => $primary->id, 'siteId' => $asset->siteId]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Compare')
            ->where('from.label', 'Light')
            ->where('to.label', 'Lumière')
            ->where('from.siteName', $primary->getName())
            ->where('to.siteName', 'Secondary')
            ->where('from.versionMenu', [])
            ->where('to.versionMenu', [])
            ->where('to.siteMenu', fn (Collection $menu) => $menu->pluck('label')->all() === [$primary->getName(), 'Secondary'])
            ->where('to.editor.siteId', $asset->siteId));
});

it('shows a non-localized element without drafts or revisions with no menus, by its label', function () {
    $user = UserModel::factory()->createElement(['admin' => false, 'firstName' => 'Ada', 'lastName' => 'Lovelace']);

    get(route('craft.cp.elements.compare', ['id' => $user->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('elements/Compare')
            ->where('from.label', $user->getUiLabel())
            ->where('to.label', $user->getUiLabel())
            ->where('to.siteName', null)
            ->where('to.siteMenu', [])
            ->where('to.versionMenu', [])
            // Users have no field layout saved by default, which pairs the fields all the same.
            ->where('rows', fn (Collection $rows) => $rows->isNotEmpty() && $rows->every(fn (array $row) => $row['fromUid'] === $row['toUid'] && $row['changed'] === false)));
});

it('lists only the versions and sites the user can view in each side’s menus', function () {
    $entry = comparedEntry(['summary' => 'Someone else’s']);
    app(Revisions::class)->createRevision($entry, auth()->id());
    app(Drafts::class)->createDraft($entry, auth()->id(), 'Their draft');
    $user = UserModel::factory()
        ->withPermissions([
            'accessCp',
            sprintf('editSite:%s', Site::query()->where('primary', true)->value('uid')),
            "viewEntries:{$this->section->uid}",
        ])
        ->createElement(['admin' => false]);
    $first = app(Drafts::class)->createDraft($entry, $user->id, 'First');
    $second = app(Drafts::class)->createDraft($entry, $user->id, 'Second');

    actingAs($user);

    get(compareScreenUrl($entry, ['draftId' => $second->draftId, 'from' => "draft-$first->draftId"]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Not the current entry, its revisions, or someone else’s draft.
            ->where('to.versionMenu', fn (Collection $menu) => $menu->flatMap(fn (array $group) => $group['items'])->pluck('label')->all() === ['First', 'Second'])
            // Nor the site the user can’t edit.
            ->where('to.siteName', null)
            ->where('to.siteMenu', []));
});
