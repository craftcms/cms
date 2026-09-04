<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Str;
use Mockery\MockInterface;

final class SplitSinglesSource
{
    /**
     * Loads the migration under test.
     */
    public static function migration(): object
    {
        return require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_02_000000_split_singles_source.php';
    }

    /**
     * The project-config path holding Entry's stored element sources.
     */
    public static function path(): string
    {
        return sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class);
    }
}

beforeEach(function () {
    $this->projectConfig = app(ProjectConfig::class);

    $this->homeUid = '11111111-1111-1111-1111-111111111111';
    $this->aboutUid = '22222222-2222-2222-2222-222222222222';
    $this->blogUid = '33333333-3333-3333-3333-333333333333';

    $this->projectConfig->set(ProjectConfig::PATH_SECTIONS, [
        $this->homeUid => ['name' => 'Home', 'handle' => 'home', 'type' => SectionType::Single->value],
        $this->blogUid => ['name' => 'Blog', 'handle' => 'blog', 'type' => SectionType::Channel->value],
        $this->aboutUid => ['name' => 'About', 'handle' => 'about', 'type' => SectionType::Single->value],
    ]);
});

it('expands a stored singles row in place', function () {
    $this->projectConfig->set(SplitSinglesSource::path(), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*', 'page' => 'Content'],
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'singles',
            'page' => 'Content',
            'tableAttributes' => ['status', 'link'],
            'defaultSort' => ['title', 'asc'],
            'defaultViewMode' => 'cards',
        ],
        ['type' => ElementSources::TYPE_HEADING, 'heading' => 'Channels', 'page' => 'Content'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->blogUid}", 'page' => 'Content'],
    ]);

    SplitSinglesSource::migration()->up();

    $sources = $this->projectConfig->get(SplitSinglesSource::path());

    expect(array_map(fn (array $s) => $s['heading'] ?? $s['key'], $sources))->toBe([
        '*',
        'Singles',
        "section:{$this->homeUid}",
        "section:{$this->aboutUid}",
        'Channels',
        "section:{$this->blogUid}",
    ]);
});

it('keys the Singles heading so the customize sources modal can keep it', function () {
    $this->projectConfig->set(SplitSinglesSource::path(), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'],
    ]);

    SplitSinglesSource::migration()->up();

    $heading = $this->projectConfig->get(SplitSinglesSource::path())[0];

    expect($heading['key'])->toStartWith('heading:')
        ->and(Str::isUuid(substr($heading['key'], strlen('heading:'))))->toBeTrue();
});

it('inherits the replaced row’s page and display settings', function () {
    $this->projectConfig->set(SplitSinglesSource::path(), [
        [
            'type' => ElementSources::TYPE_NATIVE,
            'key' => 'singles',
            'page' => 'Pages',
            'tableAttributes' => ['status', 'link'],
            'defaultSort' => ['title', 'asc'],
            'defaultViewMode' => 'cards',
            'disabled' => true,
        ],
    ]);

    SplitSinglesSource::migration()->up();

    $sources = $this->projectConfig->get(SplitSinglesSource::path());

    // Project config sorts each row's keys, so compare loosely.
    expect($sources[0])->toEqual([
        'type' => ElementSources::TYPE_HEADING,
        'key' => $sources[0]['key'],
        'heading' => 'Singles',
        'page' => 'Pages',
    ]);

    foreach ([$sources[1], $sources[2]] as $source) {
        expect($source['page'])->toBe('Pages')
            ->and($source['tableAttributes'])->toBe(['status', 'link'])
            ->and($source['defaultSort'])->toBe(['title', 'asc'])
            ->and($source['defaultViewMode'])->toBe('cards')
            ->and($source['disabled'])->toBeTrue();
    }
});

it('is a no-op on a second run', function () {
    $this->projectConfig->set(SplitSinglesSource::path(), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'],
    ]);

    SplitSinglesSource::migration()->up();
    $afterFirstRun = $this->projectConfig->get(SplitSinglesSource::path());

    SplitSinglesSource::migration()->up();

    expect($this->projectConfig->get(SplitSinglesSource::path()))->toBe($afterFirstRun);
});

it('leaves a config with no singles row untouched', function () {
    $stored = [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => '*'],
        ['type' => ElementSources::TYPE_HEADING, 'heading' => 'Channels'],
        ['type' => ElementSources::TYPE_NATIVE, 'key' => "section:{$this->blogUid}"],
    ];

    $this->projectConfig->set(SplitSinglesSource::path(), $stored);

    SplitSinglesSource::migration()->up();

    expect($this->projectConfig->get(SplitSinglesSource::path()))->toEqual($stored);
});

it('is a no-op on a fresh install with no stored sources', function () {
    $this->projectConfig->set(SplitSinglesSource::path(), null);

    SplitSinglesSource::migration()->up();

    expect($this->projectConfig->get(SplitSinglesSource::path()))->toBeNull();
});

it('restores project config event muting when the migration fails', function () {
    $this->projectConfig->set(SplitSinglesSource::path(), [
        ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'],
    ]);

    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->muteEvents = false;
    $projectConfig->shouldReceive('set')
        ->once()
        ->andThrow(new RuntimeException('Failed to update project config'));
    app()->instance(ProjectConfig::class, $projectConfig);

    expect(fn () => SplitSinglesSource::migration()->up())->toThrow(RuntimeException::class)
        ->and($projectConfig->muteEvents)->toBeFalse();
});
