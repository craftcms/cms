<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\CustomNavigations;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

/**
 * The nav item for an entry page, by its label, as the user gets it, or in the base tree.
 */
function entryPageItem(string $label, bool $base = false): ?NavItem
{
    app()->forgetScopedInstances();

    $find = function (array $items) use (&$find, $label): ?NavItem {
        foreach ($items as $item) {
            if ($item->label === $label && ! $item->group) {
                return $item;
            }

            if (is_array($item->subnav) && ($found = $find($item->subnav))) {
                return $found;
            }
        }

        return null;
    };

    $navigation = app(Navigation::class);

    return $find($base ? $navigation->getBaseTree() : $navigation->getTree());
}

function pageSources(array $sources): void
{
    app(ProjectConfig::class)->set(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class, $sources);
}

it('links a page with one source straight to it', function () {
    $channel = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'news']);

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$channel->uid", 'page' => 'News'],
    ]);

    $item = entryPageItem('News');

    expect($item->subnav)->toBeFalse()
        ->and(parse_url($item->href, PHP_URL_PATH))->toEndWith('/content/news/news');
});

it('links a page with one single to the single’s source', function () {
    $single = Section::factory()->create(['type' => SectionType::Single, 'handle' => 'contactDetails']);

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_HEADING, 'key' => 'heading:globals', 'heading' => 'Globals', 'page' => 'Globals'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$single->uid", 'page' => 'Globals'],
    ]);

    $item = entryPageItem('Globals');

    expect($item->subnav)->toBeFalse()
        ->and(parse_url($item->href, PHP_URL_PATH))->toEndWith('/content/globals/contactDetails');
});

it('keeps a page’s subnav when it has more than one source', function () {
    $news = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'news']);
    $events = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'events']);

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$news->uid", 'page' => 'News'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$events->uid", 'page' => 'News'],
    ]);

    $item = entryPageItem('News');

    expect($item->subnav)->toHaveCount(2)
        ->and(parse_url($item->href, PHP_URL_PATH))->toEndWith('/content/news');
});

it('keeps a page’s nav item id when it links to its one source', function () {
    $news = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'news']);

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$news->uid", 'page' => 'News'],
    ]);

    expect(entryPageItem('News')->id)->toBe(entryPageItem('News', base: true)->id);
});

it('applies customizations to a page’s one source before linking to it', function () {
    $news = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'news']);

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$news->uid", 'page' => 'News'],
    ]);
    $page = entryPageItem('News', base: true);
    app(CustomNavigations::class)->saveDefault(['items' => [$page->subnav[0]->id => ['hidden' => true]]]);

    $item = entryPageItem('News');

    expect($item->subnav)->toBeFalse()
        ->and(parse_url($item->href, PHP_URL_PATH))->toEndWith('/content/news');
});

it('gives a page that links to its one source the source’s badge count', function () {
    $news = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'news']);
    EntryModel::factory()->forSection($news)->count(3)->create();

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$news->uid", 'page' => 'News', 'showBadgeCount' => true],
    ]);
    $id = entryPageItem('News')->id;

    expect(app(Navigation::class)->getSourceBadgeCounts())->toHaveKey($id, 3);
});
