<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

/**
 * Puts Entry's sources on the given pages, in the shape the “Customize sources”
 * modal stores them.
 *
 * @param  array<int, array<string, mixed>>  $sources
 */
function storeEntrySources(array $sources): void
{
    app(ProjectConfig::class)->set(
        sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class),
        $sources,
    );

    // ElementSources memoizes what it read before the config changed.
    app()->forgetScopedInstances();
}

/** @return array<string, string> */
function entryNavUrls(): array
{
    return collect(app(Navigation::class)->getItems())
        ->mapWithKeys(fn (NavItem $item) => [$item->label => $item->url])
        ->all();
}

beforeEach(function () {
    actingAs(User::findOne());
});

it('links a page holding one single straight to the entry', function () {
    $section = Section::factory()->create([
        'type' => SectionType::Single,
        'name' => 'Home',
        'handle' => 'home',
    ]);
    $entry = EntryModel::factory()->forSection($section)->createElement(['title' => 'Home']);

    storeEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$section->uid", 'page' => 'Home'],
    ]);

    // The index would list the one entry you were going to open anyway.
    expect(entryNavUrls()['Home'])->toBe($entry->getCpEditUrl())
        ->and(entryNavUrls()['Entries'])->toBe(Url::url('content/entries'));
});

it('keeps the index for a page holding more than the single', function () {
    $home = Section::factory()->create([
        'type' => SectionType::Single,
        'name' => 'Home',
        'handle' => 'home',
    ]);
    $posts = Section::factory()->create([
        'type' => SectionType::Channel,
        'name' => 'Posts',
        'handle' => 'posts',
    ]);
    EntryModel::factory()->forSection($home)->createElement(['title' => 'Home']);

    storeEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$home->uid", 'page' => 'Home'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$posts->uid", 'page' => 'Home'],
    ]);

    expect(entryNavUrls()['Home'])->toBe(Url::url('content/home'));
});

it('keeps the index for a page holding one channel', function () {
    $posts = Section::factory()->create([
        'type' => SectionType::Channel,
        'name' => 'Posts',
        'handle' => 'posts',
    ]);

    storeEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$posts->uid", 'page' => 'Posts'],
    ]);

    expect(entryNavUrls()['Posts'])->toBe(Url::url('content/posts'));
});

it('ignores a heading sitting on the single’s page', function () {
    $section = Section::factory()->create([
        'type' => SectionType::Single,
        'name' => 'Home',
        'handle' => 'home',
    ]);
    $entry = EntryModel::factory()->forSection($section)->createElement(['title' => 'Home']);

    storeEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_HEADING, 'key' => 'heading:1', 'heading' => 'Singles', 'page' => 'Home'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$section->uid", 'page' => 'Home'],
    ]);

    expect(entryNavUrls()['Home'])->toBe($entry->getCpEditUrl());
});

it('keeps the index while the single has no entry yet', function () {
    $section = Section::factory()->create([
        'type' => SectionType::Single,
        'name' => 'Home',
        'handle' => 'home',
    ]);

    storeEntrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:$section->uid", 'page' => 'Home'],
    ]);

    expect(entryNavUrls()['Home'])->toBe(Url::url('content/home'));
});
