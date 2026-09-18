<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Str;

/**
 * Splits the single aggregate `singles` entry source into one `section:{uid}`
 * source per Single section, under a `Singles` heading.
 *
 * `singles` is no longer a native source key, so a stored row keyed `singles`
 * would be dropped on read and the per-single rows re-appended at the bottom of
 * the list under a blank heading. Rewriting the stored config in place keeps
 * every customized site's ordering, page assignments, and per-source settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);
        $path = sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, Entry::class);

        /** @var array<int, array<string, mixed>>|null $sources */
        $sources = $projectConfig->get($path);

        if (! is_array($sources)) {
            return;
        }

        $sources = array_values($sources);
        $index = array_find_key($sources, fn ($source) => ($source['key'] ?? null) === 'singles');

        if ($index === null) {
            return;
        }

        $replacement = $this->replacementSources($sources[$index], $projectConfig);

        array_splice($sources, $index, 1, $replacement);

        $muteEvents = $projectConfig->muteEvents;
        $projectConfig->muteEvents = true;

        try {
            $projectConfig->set($path, $sources, 'Split the “Singles” entry source into per-section sources');
            $projectConfig->saveModifiedConfigData();
        } finally {
            $projectConfig->muteEvents = $muteEvents;
        }
    }

    public function down(): void
    {
        $this->output->error('2026_09_02_000000_split_singles_source cannot be reverted.');
    }

    /**
     * Builds the rows that replace the aggregate `singles` row: a `Singles`
     * heading plus one native source per Single section, each inheriting the
     * replaced row's page and display settings.
     *
     * Single section UIDs come straight from project config rather than the
     * `Sections` service, so the migration doesn't depend on service state.
     *
     * @param  array<string, mixed>  $source
     * @return array<int, array<string, mixed>>
     */
    private function replacementSources(array $source, ProjectConfig $projectConfig): array
    {
        $page = $source['page'] ?? null;

        $inherited = array_filter([
            'tableAttributes' => $source['tableAttributes'] ?? null,
            'defaultSort' => $source['defaultSort'] ?? null,
            'defaultViewMode' => $source['defaultViewMode'] ?? null,
            'disabled' => $source['disabled'] ?? null,
        ], fn (mixed $value) => $value !== null);

        $rows = [];

        /** @var array<string, array<string, mixed>> $sections */
        $sections = $projectConfig->get(ProjectConfig::PATH_SECTIONS) ?? [];

        foreach ($sections as $uid => $section) {
            if (($section['type'] ?? null) !== SectionType::Single->value) {
                continue;
            }

            $rows[] = array_filter([
                'type' => ElementSources::TYPE_NATIVE,
                'key' => "section:$uid",
                'page' => $page,
                ...$inherited,
            ], fn (mixed $value) => $value !== null);
        }

        if ($rows === []) {
            return [];
        }

        // Stored headings render as-is, so keep this locale-independent rather
        // than baking in whatever language the migration happened to run under.
        // The key is what lets the “Customize sources” modal rename or move the
        // heading, and keeps it from being dropped on the next save.
        $heading = array_filter([
            'type' => ElementSources::TYPE_HEADING,
            'key' => 'heading:'.Str::uuid()->toString(),
            'heading' => 'Singles',
            'page' => $page,
        ], fn (mixed $value) => $value !== null);

        return [$heading, ...$rows];
    }
};
