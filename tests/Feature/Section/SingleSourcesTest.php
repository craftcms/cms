<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());
});

function singleSection(string $handle): Section
{
    return Section::factory()->create([
        'type' => SectionType::Single,
        'name' => ucfirst($handle),
        'handle' => $handle,
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

it('gives each single a source of its own, under a Singles heading', function () {
    $single = singleSection('contact');

    $sources = app(ElementSources::class)->getSources(Entry::class);
    $source = $sources->firstWhere('key', "section:$single->uid");
    $headingIndex = $sources->search(fn (array $s) => ($s['heading'] ?? null) === 'Singles');

    expect($source['label'])->toBe('Contact')
        ->and($source['defaultSort'])->toBe(['title', 'asc'])
        ->and($headingIndex)->toBeInt()
        ->and($sources->search($source))->toBeGreaterThan($headingIndex)
        ->and(Entry::defaultTableAttributes("section:$single->uid"))->toBe(['status', 'link'])
        ->and(entrySourceKeys())->not->toContain('singles');
});

it('links a single by its handle', function () {
    $single = Sections::getSectionById(singleSection('contact')->id);

    expect($single->getCpIndexUri())->toBe('content/entries/contact');
});

it('enables a new single’s source when the sources were customized', function () {
    entrySources([
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
    ]);

    $single = singleSection('contact');
    app()->forgetScopedInstances();

    expect(entrySourceKeys())->toContain("section:$single->uid");
});

describe('crumbs', function () {
    beforeEach(function () {
        $this->contact = singleSection('contact');
        $this->entry = EntryModel::factory()->forSection($this->contact)->createElement(['title' => 'Northwind']);
        $this->news = Section::factory()->create(['type' => SectionType::Channel, 'handle' => 'news']);
    });

    it('leads with the page when the single shares it', function () {
        entrySources([
            ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->contact->uid}", 'page' => 'Globals'],
            ['type' => ElementSources::TYPE_HEADING, 'key' => 'heading:more', 'heading' => 'More', 'page' => 'Globals'],
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->news->uid}", 'page' => 'Globals'],
        ]);

        $crumbs = Entry::find()->id($this->entry->id)->one()->getCrumbs();

        expect(array_map(fn ($crumb) => $crumb['label'], $crumbs))->toBe(['Globals'])
            ->and(parse_url($crumbs[0]['href'], PHP_URL_PATH))->toEndWith('/content/globals');
    });

    it('has none when the single is its page’s only source', function () {
        entrySources([
            ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->contact->uid}", 'page' => 'Contact'],
        ]);

        expect(Entry::find()->id($this->entry->id)->one()->getCrumbs())->toBe([]);
    });

    it('only has the page when the single’s source is disabled', function () {
        entrySources([
            ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Entries'],
            ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->contact->uid}", 'page' => 'Entries', 'disabled' => true],
        ]);

        expect(array_map(fn ($crumb) => $crumb['label'], Entry::find()->id($this->entry->id)->one()->getCrumbs()))->toBe(['Entries']);
    });
});
