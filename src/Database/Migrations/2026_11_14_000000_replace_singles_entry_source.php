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
 * own sources disabled, on a “Singles” page before the other pages, so the
 * sources look the same as before.
 */
return new class extends Migration
{
    /** The page sources were shown on before they had pages. */
    private const string DEFAULT_PAGE = 'Entries';

    private const string SINGLES_PAGE = 'Singles';

    /** The CP nav section Craft lists entry pages in. */
    private const string CONTENT_SECTION = 'nav-group-content';

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

        // Sources saved before Craft 5.9 have no pages, and were all shown on
        // the Entries page. Once one source has a page, those without one aren’t
        // shown, so they’re given the page they were shown on.
        if (! isset($sources[0]['page'])) {
            $sources = array_map(fn (array $source) => [...$source, 'page' => $source['page'] ?? self::DEFAULT_PAGE], $sources);
        }

        $index = array_find_key($sources, fn (array $source) => $this->isSinglesSource($source));

        if ($index !== null) {
            $singlesSource = $sources[$index];
            array_splice($sources, $index, 1);
            $page = $singlesSource['page'] ?? self::DEFAULT_PAGE;
        } else {
            // A Singles source missing from the saved sources was shown at the
            // end of the first page, after a blank heading if there were any.
            $index = count($sources);
            $page = $sources[0]['page'] ?? self::DEFAULT_PAGE;

            if ($singleUids !== []) {
                $singlesSource = ['type' => ElementSources::TYPE_NATIVE, 'key' => 'singles'];

                if (array_any($sources, fn (array $source) => ($source['type'] ?? null) === ElementSources::TYPE_HEADING)) {
                    $sources[] = ['type' => ElementSources::TYPE_HEADING, 'heading' => '', 'page' => $page];
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

        array_splice($sources, $index, 0, $newSources);

        // The singles’ own sources go on a Singles page, before the other pages,
        // which are ordered by their sources.
        $existingKeys = array_column($sources, 'key');
        $singleSources = [];

        foreach ($singleUids as $uid) {
            if (! in_array("section:$uid", $existingKeys, true)) {
                $singleSources[] = [
                    'type' => ElementSources::TYPE_NATIVE,
                    'key' => "section:$uid",
                    'page' => self::SINGLES_PAGE,
                    'disabled' => true,
                ];
            }
        }

        $sources = [...$singleSources, ...$sources];

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

        if ($singleSources !== []) {
            $this->orderSinglesPage($projectConfig, array_column($sources, 'page'));
        }

        $projectConfig->saveModifiedConfigData();
    }

    /**
     * Lists the Singles page before the other entry pages in the default CP
     * navigation, if it was saved with an order for them. Unlisted items
     * follow the listed ones, so the Singles page would come after them.
     *
     * It goes before the first entry page in the content section’s order, or
     * in the order of whichever section, or the root, the entry pages were
     * moved to.
     *
     * @param  list<string>  $pages  The pages of the entry sources
     */
    private function orderSinglesPage(ProjectConfig $projectConfig, array $pages): void
    {
        $path = ProjectConfig::PATH_CP_NAV.'.default';
        $nav = $projectConfig->get($path);

        if (! is_array($nav) || ! is_array($nav['order'] ?? null)) {
            return;
        }

        $singlesId = $this->pageNavItemId(self::SINGLES_PAGE);

        if (array_any($nav['order'], fn (mixed $ids) => is_array($ids) && in_array($singlesId, $ids, true))) {
            return;
        }

        $pageIds = array_map(
            $this->pageNavItemId(...),
            array_values(array_diff(array_unique($pages), [self::SINGLES_PAGE])),
        );

        // The content section first, where Craft lists entry pages.
        $order = $nav['order'];
        uksort($order, fn (string $a, string $b) => ($b === self::CONTENT_SECTION) <=> ($a === self::CONTENT_SECTION));

        foreach ($order as $parent => $ids) {
            if (! is_array($ids)) {
                continue;
            }

            $index = array_find_key($ids, fn (mixed $id) => in_array($id, $pageIds, true));

            if ($index === null) {
                continue;
            }

            array_splice($ids, $index, 0, [$singlesId]);
            $nav['order'][$parent] = $ids;

            if ($parent !== self::CONTENT_SECTION) {
                $nav['items'][$singlesId]['parent'] = $parent;
            }

            $projectConfig->set($path, $nav, 'List the Singles entry page before the other entry pages');

            return;
        }
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
    private function updateNavigations(ProjectConfig $projectConfig, string $page, string $customKey): void
    {
        $indexUri = sprintf('content/%s', ElementSources::pageSlug($page));
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

        return $this->navItemId(sprintf('%s/source-%s', $indexUri, $escapedKey));
    }

    /**
     * An entry page’s nav item ID, as {@see Navigation} gives it.
     */
    private function pageNavItemId(string $page): string
    {
        return $this->navItemId(sprintf('content/%s', ElementSources::pageSlug($page)));
    }

    private function navItemId(string $href): string
    {
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
    private function customSinglesSource(array $singlesSource, string $page): array
    {
        $condition = Entry::createCondition();
        $condition->addConditionRule($condition->createConditionRule([
            'class' => SectionTypeConditionRule::class,
            'values' => [SectionType::Single->value],
        ]));

        return array_filter([
            'type' => ElementSources::TYPE_CUSTOM,
            'key' => 'custom:'.Str::uuid(),
            'label' => 'Singles',
            'condition' => $condition->getConfig(),
            // The Singles source’s defaults
            'tableAttributes' => $singlesSource['tableAttributes'] ?? ['status', 'link'],
            'defaultSort' => $singlesSource['defaultSort'] ?? ['title', 'asc'],
            'defaultViewMode' => $singlesSource['defaultViewMode'] ?? null,
            'showBadgeCount' => $singlesSource['showBadgeCount'] ?? null,
            'page' => $page,
        ], fn (mixed $value) => $value !== null);
    }

    public function down(): void {}
};
