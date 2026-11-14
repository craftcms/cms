<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Str;
use Illuminate\Database\Migrations\Migration;

/**
 * Singles now have entry sources of their own, rather than sharing a `singles`
 * source. This replaces an enabled `singles` source with a custom “Singles”
 * source that lists the same entries the same way, and adds existing singles’
 * own sources disabled, so the sources look the same as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        $projectConfig = app(ProjectConfig::class);
        $path = ProjectConfig::PATH_ELEMENT_SOURCES.'.'.Entry::class;
        $singleUids = $this->singleUids();
        $sources = $projectConfig->get($path);

        if (empty($sources)) {
            // Sources that were never customized showed the Singles source
            // after “All entries”, so save them as they were shown.
            if ($singleUids === []) {
                return;
            }

            $sources = $this->defaultSources();
        }

        $sources = array_values($sources);
        $index = array_find_key($sources, fn (array $source) => $this->isSinglesSource($source));

        if ($index !== null) {
            $singlesSource = $sources[$index];
            array_splice($sources, $index, 1);
            $page = $singlesSource['page'] ?? null;
        } else {
            // A Singles source missing from the saved sources was shown at the
            // end of the first page, after a blank heading if there were any.
            $index = count($sources);
            $page = $sources[0]['page'] ?? null;

            if ($singleUids !== []) {
                $singlesSource = ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'];

                if (array_any($sources, fn (array $source) => ($source['type'] ?? null) === ElementSources::TYPE_HEADING)) {
                    $sources[] = $this->withPage(['type' => ElementSources::TYPE_HEADING, 'heading' => ''], $page);
                    $index++;
                }
            }
        }

        $newSources = [];

        if (isset($singlesSource) && empty($singlesSource['disabled']) && $singleUids !== []) {
            $newSources[] = $this->customSinglesSource($singlesSource, $page);
        }

        $existingKeys = array_column($sources, 'key');

        foreach ($singleUids as $uid) {
            if (! in_array("section:$uid", $existingKeys, true)) {
                $newSources[] = $this->withPage([
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => "section:$uid",
                    'disabled' => true,
                ], $page);
            }
        }

        array_splice($sources, $index, 0, $newSources);

        if ($sources === $projectConfig->get($path)) {
            return;
        }

        $projectConfig->set($path, $sources, 'Replace the Singles entry source');
        $projectConfig->saveModifiedConfigData();
    }

    /**
     * The single sections’ UIDs, in the order their sources are defined in.
     *
     * @return list<string>
     */
    private function singleUids(): array
    {
        return Sections::getSectionsByType(SectionType::Single)
            ->map(fn (Section $section) => (string) $section->uid)
            ->values()
            ->all();
    }

    /**
     * The sources shown before they were customized, with the Singles source
     * after “All entries”.
     *
     * @return list<array<string, mixed>>
     */
    private function defaultSources(): array
    {
        $sources = [];

        foreach (app(ElementSources::class)->getSources(Entry::class, ElementSources::CONTEXT_SETTINGS, true) as $source) {
            if ($source['type'] === ElementSources::TYPE_HEADING) {
                // Singles had no heading of their own.
                if (($source['handle'] ?? null) !== SectionType::Single->value) {
                    $sources[] = ['type' => ElementSources::TYPE_HEADING, 'heading' => $source['heading']];
                }

                continue;
            }

            if (($source['data']['type'] ?? null) === SectionType::Single->value) {
                continue;
            }

            $sources[] = ['type' => $source['type'], 'key' => $source['key']];

            if ($source['key'] === '*') {
                $sources[] = ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'];
            }
        }

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function isSinglesSource(array $source): bool
    {
        return ($source['type'] ?? ElementSources::TYPE_NATIVE) === ElementSources::TYPE_NATIVE && ($source['key'] ?? null) === 'singles';
    }

    /**
     * @param  array<string, mixed>  $singlesSource
     * @return array<string, mixed>
     */
    private function customSinglesSource(array $singlesSource, ?string $page): array
    {
        $condition = Entry::createCondition();
        $condition->addConditionRule($condition->createConditionRule([
            'class' => SectionTypeConditionRule::class,
            'values' => [SectionType::Single->value],
        ]));

        return $this->withPage(array_filter([
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:'.Str::uuid(),
            'label' => 'Singles',
            'condition' => $condition->getConfig(),
            // The Singles source’s defaults
            'tableAttributes' => $singlesSource['tableAttributes'] ?? ['status', 'link'],
            'defaultSort' => $singlesSource['defaultSort'] ?? ['title', 'asc'],
            'defaultViewMode' => $singlesSource['defaultViewMode'] ?? null,
            'showBadgeCount' => $singlesSource['showBadgeCount'] ?? null,
        ], fn (mixed $value) => $value !== null), $page);
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function withPage(array $source, ?string $page): array
    {
        return $page !== null ? [...$source, 'page' => $page] : $source;
    }

    public function down(): void {}
};
