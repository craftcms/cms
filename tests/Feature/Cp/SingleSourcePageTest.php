<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

/**
 * The nav item for an entry page, by its label.
 */
function entryPageItem(string $label): ?NavItem
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

    return $find(app(Navigation::class)->getBaseTree());
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
    $single = Section::factory()->create(['type' => SectionType::Single, 'handle' => 'contactDetails', 'inSinglesSource' => false]);

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
    $events = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'events']);

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$news->uid", 'page' => 'News'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$events->uid", 'page' => 'News'],
    ]);
    $id = entryPageItem('News')->id;

    pageSources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$news->uid", 'page' => 'News'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$events->uid", 'page' => 'Events'],
    ]);

    expect(entryPageItem('News')->id)->toBe($id);
});
