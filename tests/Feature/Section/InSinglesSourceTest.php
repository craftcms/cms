<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

function singleSection(string $handle, bool $inSinglesSource): Section
{
    return Section::factory()->create([
        'type' => SectionType::Single,
        'name' => ucfirst($handle),
        'handle' => $handle,
        'inSinglesSource' => $inSinglesSource,
    ]);
}

function entrySources(array $sources = []): void
{
    app(ProjectConfig::class)->set(ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class, $sources ?: null);
    app()->forgetScopedInstances();
}

/** @return list<string> */
function entrySourceKeys(): array
{
    return app(ElementSources::class)->getSources(Entry::class)->pluck('key')->filter()->values()->all();
}

it('lists a single in the Singles source when it’s included in it', function () {
    $single = singleSection('home', true);

    $singles = app(ElementSources::class)->findSource(Entry::class, 'singles');

    expect($singles['criteria']['sectionId'])->toBe([$single->id])
        ->and(entrySourceKeys())->not->toContain("section:$single->uid");
});

it('gives a single a source of its own when it isn’t', function () {
    $single = singleSection('contact', false);

    $source = app(ElementSources::class)->findSource(Entry::class, "section:$single->uid");

    expect($source['label'])->toBe('Contact')
        ->and($source['defaultSort'])->toBe(['title', 'asc'])
        ->and(Entry::defaultTableAttributes("section:$single->uid"))->toBe(['status', 'link'])
        // With no single in it, there's no Singles source to list.
        ->and(entrySourceKeys())->not->toContain('singles');
});

it('links each single by the source it’s listed in', function () {
    $grouped = Sections::getSectionById(singleSection('home', true)->id);
    $own = Sections::getSectionById(singleSection('contact', false)->id);

    expect($grouped->getSourceKey())->toBe('singles')
        ->and($grouped->getCpIndexUri())->toBe('content/entries/singles')
        ->and($own->getSourceKey())->toBe("section:$own->uid")
        ->and($own->getCpIndexUri())->toBe('content/entries/contact');
});

it('starts new singles with a source of their own', function () {
    expect(new SectionData()->inSinglesSource)->toBeFalse();
});

it('only saves the setting for singles', function () {
    $single = Sections::getSectionById(singleSection('home', false)->id);
    $channel = Sections::getSectionById(Section::factory()->create(['type' => SectionType::Channel])->id);

    expect($single->getConfig())->toHaveKey('inSinglesSource', false)
        ->and($channel->getConfig())->not->toHaveKey('inSinglesSource');
});

it('keeps a single in the Singles source when its config predates the setting', function () {
    $single = new SectionData([
        'name' => 'Home',
        'handle' => 'home',
        'type' => SectionType::Single,
        'entryTypes' => [EntryType::factory()->create()->id],
        'siteSettings' => [new SectionSiteSettingsData(['siteId' => Sites::getCurrentSite()->id])],
    ]);
    Sections::saveSection($single);

    expect(Section::find($single->id)->inSinglesSource)->toBeFalse();

    $config = $single->getConfig();
    unset($config['inSinglesSource']);
    Sections::handleChangedSection(new class(path: ProjectConfig::PATH_SECTIONS.".$single->uid", newValue: $config, tokenMatches: [$single->uid]) extends ConfigEvent {});

    expect(Section::find($single->id)->inSinglesSource)->toBeTrue();
});
