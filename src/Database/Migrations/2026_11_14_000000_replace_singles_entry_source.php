<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Conditions\SectionTypeConditionRule;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
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
        $customKey = null;

        if (isset($singlesSource) && empty($singlesSource['disabled']) && $singleUids !== []) {
            $newSources[] = $customSource = $this->customSinglesSource($singlesSource, $page);
            $customKey = $customSource['key'];
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

        if ($sources !== $projectConfig->get($path)) {
            $projectConfig->set($path, $sources, 'Replace the Singles entry source');
        }

        if ($singleUids !== []) {
            // Fields that could relate entries from the Singles source get the
            // source that replaced it, or the singles’ own sources if there isn’t one.
            $this->updateFieldSources($projectConfig, $customKey !== null ? [$customKey] : array_map(fn (string $uid) => "section:$uid", $singleUids));
        }

        if ($customKey !== null) {
            $this->updateNavigations($projectConfig, $page, $customKey);
        }

        $projectConfig->saveModifiedConfigData();
    }

    /**
     * Replaces `singles` in fields’ entry source settings: Entries fields’
     * sources, and the entry sources of Link and Markdown fields’ entry links.
     *
     * @param  list<string>  $replacements
     */
    private function updateFieldSources(ProjectConfig $projectConfig, array $replacements): void
    {
        foreach ($projectConfig->get(ProjectConfig::PATH_FIELDS) ?? [] as $uid => $config) {
            if (! is_array($config['settings'] ?? null)) {
                continue;
            }

            $settings = ProjectConfigHelper::unpackAssociativeArrays($config['settings']);
            $updated = match ($config['type'] ?? null) {
                Entries::class => $this->replaceInSources($settings, $replacements),
                Link::class => $this->replaceInSources($settings['typeSettings']['entry'], $replacements),
                Markdown::class => $this->replaceInSources($settings['linkSettingsTypeSettings']['entry'], $replacements),
                default => false,
            };

            if ($updated) {
                $projectConfig->set(
                    ProjectConfig::PATH_FIELDS.".$uid.settings",
                    ProjectConfigHelper::packAssociativeArrays($settings),
                    'Replace the Singles entry source in field settings',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @param  list<string>  $replacements
     */
    private function replaceInSources(?array &$settings, array $replacements): bool
    {
        $updated = false;

        if (is_array($settings['sources'] ?? null) && in_array('singles', $settings['sources'], true)) {
            $settings['sources'] = array_values(array_unique(array_merge(...array_map(
                fn (mixed $source) => $source === 'singles' ? $replacements : [$source],
                $settings['sources'],
            ))));
            $updated = true;
        }

        if (($settings['source'] ?? null) === 'singles') {
            $settings['source'] = $replacements[0];
            $updated = true;
        }

        return $updated;
    }

    /**
     * Points CP navigation customizations of the Singles source’s nav item at
     * the custom source that replaced it, in the default navigation and the
     * user groups’ navigations.
     */
    private function updateNavigations(ProjectConfig $projectConfig, ?string $page, string $customKey): void
    {
        $indexUri = sprintf('content/%s', ElementSources::pageSlug($page ?? 'Entries'));
        $oldId = $this->sourceNavItemId($indexUri, 'singles');
        $newId = $this->sourceNavItemId($indexUri, $customKey);
        $paths = [ProjectConfig::PATH_CP_NAV.'.default'];

        foreach (array_keys($projectConfig->get(ProjectConfig::PATH_CP_NAV.'.groups') ?? []) as $groupUid) {
            $paths[] = ProjectConfig::PATH_CP_NAV.".groups.$groupUid.nav";
        }

        foreach ($paths as $path) {
            $nav = $projectConfig->get($path);

            if (! is_array($nav)) {
                continue;
            }

            $updated = $nav;

            if (isset($updated['items'][$oldId])) {
                $updated['items'][$newId] = $updated['items'][$oldId];
                unset($updated['items'][$oldId]);
            }

            foreach ($updated['order'] ?? [] as $parentId => $childIds) {
                if (is_array($childIds)) {
                    $updated['order'][$parentId] = array_map(fn (mixed $id) => $id === $oldId ? $newId : $id, $childIds);
                }
            }

            if ($updated !== $nav) {
                $projectConfig->set($path, $updated, 'Replace the Singles entry source in the CP navigation');
            }
        }
    }

    /**
     * A source’s nav item ID, as {@see Navigation} gives it.
     */
    private function sourceNavItemId(string $indexUri, string $key): string
    {
        $escapedKey = preg_replace_callback('/[^A-Za-z0-9\-]/', fn (array $match) => '_'.bin2hex($match[0]), $key);
        $href = sprintf('%s/source-%s', $indexUri, $escapedKey);

        return 'nav-'.preg_replace('/[^\w\-_]/', '', Str::ascii(str_replace('/', '-', $href)));
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
