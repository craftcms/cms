<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Element\ElementSourceUi;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
});

it('counts every entry in a section, enabled or not, leaving out drafts and trashed entries', function () {
    $section = Section::factory()->create();
    $other = Section::factory()->create();

    $live = EntryModel::factory()->forSection($section)->createElement();
    $disabled = EntryModel::factory()->forSection($section)->createElement();
    $disabled->enabled = false;
    Elements::saveElement($disabled);

    $trashed = EntryModel::factory()->forSection($section)->createElement();
    Elements::deleteElement($trashed);

    app(Drafts::class)->createDraft($live, User::findOne()->id);
    EntryModel::factory()->forSection($other)->createElement();

    expect(Entry::sourceBadgeCount([
        'type' => ElementSources::TYPE_NATIVE,
        'key' => "section:$section->uid",
        'criteria' => ['sectionId' => $section->id, 'editable' => true],
    ], null))->toBe(2);
});

it('applies a custom source’s condition when counting', function () {
    $section = Section::factory()->create();
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Keep']);
    EntryModel::factory()->forSection($section)->createElement(['title' => 'Skip']);

    $condition = Entry::createCondition();
    $condition->addConditionRule($condition->createConditionRule([
        'class' => TitleConditionRule::class,
        'operator' => '=',
        'value' => 'Keep',
    ]));

    expect(Entry::sourceBadgeCount([
        'type' => ElementSources::TYPE_CUSTOM,
        'key' => 'custom:keep',
        'condition' => $condition->getConfig(),
    ], null))->toBe(1);
});

it('only offers counts on sources that can have one', function () {
    expect(Entry::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_NATIVE, 'key' => '*']))->toBeTrue()
        ->and(Entry::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_HEADING, 'heading' => 'Channels']))->toBeFalse()
        ->and(Entry::sourceSupportsBadgeCount(['heading' => 'Channels']))->toBeFalse()
        // A new custom source's blank config carries an empty heading too.
        ->and(Entry::sourceSupportsBadgeCount(app(ElementSourceUi::class)->blankSource(ElementSources::TYPE_CUSTOM, 'custom:new')))->toBeTrue()
        ->and(User::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_NATIVE, 'key' => 'admins']))->toBeTrue()
        ->and(Asset::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_NATIVE, 'key' => 'volume:abc']))->toBeTrue()
        ->and(Asset::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_NATIVE, 'key' => 'temp']))->toBeFalse()
        ->and(Asset::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_CUSTOM, 'key' => 'custom:1']))->toBeTrue()
        ->and(Element::sourceSupportsBadgeCount(['type' => ElementSources::TYPE_NATIVE, 'key' => '*']))->toBeFalse();
});

it('shows a count when an admin turns it on, or the source does by default', function () {
    $elementSources = app(ElementSources::class);

    expect($elementSources->showsBadgeCount(Entry::class, ['type' => ElementSources::TYPE_NATIVE, 'key' => '*']))->toBeFalse()
        ->and($elementSources->showsBadgeCount(Entry::class, ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'showBadgeCount' => true]))->toBeTrue()
        ->and($elementSources->showsBadgeCount(Entry::class, ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'defaultBadgeCount' => true]))->toBeTrue()
        ->and($elementSources->showsBadgeCount(Entry::class, [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => '*',
            'defaultBadgeCount' => true,
            'showBadgeCount' => false,
        ]))->toBeFalse()
        ->and($elementSources->showsBadgeCount(Element::class, ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'showBadgeCount' => true]))->toBeFalse();
});

it('keeps the setting when sources are saved', function () {
    app(ElementSources::class)->saveSources(Entry::class, [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'showBadgeCount' => 'true'],
        ['type' => ElementSources::TYPE_CUSTOM, 'key' => 'custom:1', 'label' => 'Mine', 'showBadgeCount' => false],
    ]);

    $config = app(ProjectConfig::class)->get(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class));

    expect($config[0]['showBadgeCount'])->toBeTrue()
        ->and($config[1]['showBadgeCount'])->toBeFalse();
});

it('gives the nav a count for each source that shows one', function () {
    EntryModel::factory()->count(3)->create();

    app(ProjectConfig::class)->set(sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'showBadgeCount' => true],
    ]);

    $navigation = app(Navigation::class);
    $counts = $navigation->getSourceBadgeCounts();

    expect($navigation->hasSourceBadges())->toBeTrue()
        ->and($counts)->toBe(['nav-content-entries-source-' => Entry::find()->status(null)->count()])
        ->and($counts['nav-content-entries-source-'])->toBeGreaterThanOrEqual(3);
});

it('leaves the nav without counts when no source shows one', function () {
    $navigation = app(Navigation::class);

    expect($navigation->hasSourceBadges())->toBeFalse()
        ->and($navigation->getSourceBadgeCounts())->toBe([]);
});

it('counts only the entries a user can view in a native source', function () {
    $section = Section::factory()->create();
    $editor = UserModel::factory()
        ->withPermissions(['accessCp', "viewEntries:$section->uid"])
        ->createElement(['admin' => false]);

    $own = EntryModel::factory()->forSection($section)->createElement();
    $own->setAuthorIds([$editor->id]);
    Elements::saveElement($own);
    EntryModel::factory()->forSection($section)->createElement();

    $source = [
        'type' => ElementSources::TYPE_NATIVE,
        'key' => "section:$section->uid",
        'criteria' => ['sectionId' => $section->id, 'editable' => true],
    ];

    expect(Entry::sourceBadgeCount($source, null))->toBe(2);

    actingAs($editor);

    // Peer entries are out of reach without “View other users’ entries”.
    expect(Entry::sourceBadgeCount($source, null))->toBe(1);
});
