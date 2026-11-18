<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\Element\Conditions\SiteConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionConditionRule;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('shows only entries that match its entry criteria', function () {
    $news = Section::factory()->create(['name' => 'News', 'type' => SectionType::Channel]);
    $blog = Section::factory()->create(['name' => 'Blog', 'type' => SectionType::Channel]);
    Entry::factory()->forSection($news)->createElement(['title' => 'News Entry']);
    $blogEntry = Entry::factory()
        ->forSection($blog)
        ->state(['postDate' => '2025-01-15 12:00:00'])
        ->createElement(['title' => 'Blog Entry']);

    $widget = new RecentEntries([
        'condition' => [
            'conditionRules' => [
                ['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => [$blog->uid]],
            ],
        ],
    ]);

    $entry = $widget->props()['entries'][0];

    expect(array_column($widget->props()['entries'], 'id'))->toBe([$blogEntry->id])
        ->and($entry['url'])->toBe($blogEntry->getCpEditUrl())
        ->and($entry['title'])->toBe('Blog Entry')
        ->and($entry['section'])->toBeNull()
        ->and($widget->props()['entries'][0]['dateCreated'])->toBe($blogEntry->dateCreated->format(DATE_ATOM))
        ->and($widget->props()['entries'][0]['dateLabel'])->toBe(
            new IntlDateFormatter('en_US', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, Cms::timezone())
                ->format($blogEntry->dateCreated->toDateTime())
        )
        ->and($widget->getTitle())->toBe('Recent Blog Entries');
});

it('maps legacy section settings to entry criteria', function () {
    $news = Section::factory()->create(['name' => 'News', 'type' => SectionType::Channel]);
    $blog = Section::factory()->create(['name' => 'Blog', 'type' => SectionType::Channel]);
    $single = Section::factory()->create(['name' => 'Home', 'type' => SectionType::Single]);
    $newsEntry = Entry::factory()->forSection($news)->createElement(['title' => 'News Entry']);
    $blogEntry = Entry::factory()->forSection($blog)->createElement(['title' => 'Blog Entry']);
    Entry::factory()->forSection($single)->createElement(['title' => 'Home']);

    $sectionWidget = new RecentEntries(['section' => (string) $news->id]);
    $allWidget = new RecentEntries(['section' => '*']);

    expect(array_column($sectionWidget->props()['entries'], 'id'))->toBe([$newsEntry->id])
        ->and($sectionWidget->getTitle())->toBe('Recent News Entries')
        ->and($sectionWidget->getSettings())->not->toHaveKeys(['section', 'siteId'])
        ->and($allWidget->getCondition()->getConditionRules()->getRules()[0])->toBeInstanceOf(SectionTypeConditionRule::class)
        ->and(array_column($allWidget->props()['entries'], 'id'))->toEqualCanonicalizing([$newsEntry->id, $blogEntry->id])
        ->and(array_column($allWidget->props()['entries'], 'section'))->toEqualCanonicalizing(['News', 'Blog']);
});

it('titles itself after its section unless it has a custom title', function () {
    $blog = Section::factory()->create(['name' => 'Blog', 'type' => SectionType::Channel]);
    $otherSite = Site::factory()->create(['name' => 'Other Site']);
    Sites::refreshSites();
    $condition = [
        'conditionRules' => [
            ['class' => SectionConditionRule::class, 'operator' => 'in', 'values' => [$blog->uid]],
            ['class' => SiteConditionRule::class, 'operator' => 'in', 'values' => [$otherSite->uid]],
        ],
    ];
    $placeholder = function (RecentEntries $widget): ?string {
        $nodes = flattenUiNodes(array_map(
            fn ($node): array => $node->jsonSerialize(),
            app(UiResolver::class)->resolve($widget->settingsUi(), new UiContext(namespace: 'settings'))->nodes,
        ));

        return collect($nodes)->firstWhere('control.path', ['settings', 'customTitle'])['control']['props']['placeholder'];
    };

    $default = new RecentEntries(['condition' => $condition, 'customTitle' => '']);
    $custom = new RecentEntries(['condition' => $condition, 'customTitle' => 'Fresh Posts']);

    expect($default->getTitle())->toBe('Recent Blog Entries')
        ->and($placeholder($default))->toBe('Recent Blog Entries')
        ->and($custom->getTitle())->toBe('Fresh Posts')
        ->and($placeholder($custom))->toBe('Recent Blog Entries');
});

it('credits entries to their authors by name, with title-less thumbnails', function () {
    $section = Section::factory()->create(['type' => SectionType::Channel]);
    $author = UserModel::factory()->create(['username' => 'jdoe', 'fullName' => 'Jane Doe']);
    $entry = Entry::factory()->forSection($section)->createElement();
    $entry->setAuthorIds([$author->id]);
    Elements::saveElement($entry);

    $authors = (new RecentEntries)->props()['entries'][0]['authors'];

    expect(array_column($authors, 'name'))->toBe(['Jane Doe'])
        ->and($authors[0]['thumbHtml'])->not->toBeNull()
        ->and($authors[0]['thumbHtml'])->not->toContain('<title>')
        ->and($authors[0]['thumbHtml'])->not->toContain('role="img"')
        ->and($authors[0]['thumbHtml'])->toContain('aria-hidden="true"');
});
