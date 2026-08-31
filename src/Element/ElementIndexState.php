<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Cp\Html\ElementIndexHtml;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\ViewModels\ContentIndexViewModel;
use CraftCms\Cms\Site\Sites;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;

/**
 * Resolves the server-side state of an element index — the parts of an index
 * screen that are decided before any elements are queried: which sources it
 * lists, which table columns and sort options are selectable, and whether the
 * site/status menus apply.
 *
 * This is the single implementation shared by the Inertia payload
 * ({@see ContentIndexViewModel}) and the server-rendered index shell
 * ({@see ElementIndexHtml}, used by the element-selector modal and the
 * remaining legacy screens), which had grown independent copies of all of it.
 *
 * Query building is {@see ElementIndexes}' job; this class never touches a
 * query.
 *
 * @phpstan-import-type SourceConfig from ElementSources
 *
 * @phpstan-type NormalizedSortOption array{
 *     key: array-key,
 *     label: mixed,
 *     attribute: mixed,
 *     defaultDir: mixed,
 *     option: mixed,
 * }
 */
#[Scoped]
readonly class ElementIndexState
{
    public function __construct(
        private ElementSources $elementSources,
    ) {}

    /**
     * The index's sources, optionally restricted to a given set of source keys.
     *
     * `$restrictTo` is how the element-selector modal narrows an index down to
     * the sources a field is configured for: headings are kept (then pruned
     * back down if they end up empty), and any requested key that isn't a
     * top-level source is looked up individually and slotted in behind the key
     * it was listed after.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  string[]|null  $restrictTo  Source keys to limit the list to, or `null` for every source
     * @return Collection<int, SourceConfig>
     */
    public function sources(
        string $elementType,
        string $context = ElementSources::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
        ?array $restrictTo = null,
    ): Collection {
        $allSources = $this->elementSources->getSources($elementType, $context, $withDisabled, $page);

        if ($restrictTo === null) {
            return $allSources;
        }

        $restrictTo = array_values($restrictTo);
        // Flipped so found keys can be unset, leaving the ones we couldn't place
        $missingKeys = array_flip($restrictTo);
        $sources = [];

        foreach ($allSources as $source) {
            if ($source['type'] === ElementSources::TYPE_HEADING) {
                $sources[] = $source;
            } elseif (isset($missingKeys[$source['key']])) {
                $sources[] = $source;
                unset($missingKeys[$source['key']]);
            }
        }

        $sources = ElementSources::filterExtraHeadings($sources)->all();

        // Whatever's left is probably a nested source, which getSources() doesn't
        // surface at the top level.
        foreach (array_keys($missingKeys) as $key) {
            $source = $this->elementSources->findSource($elementType, (string) $key, $context, $withDisabled, $page);

            if ($source === null) {
                continue;
            }

            $inserted = false;
            // If it was listed after another source key that made it in, insert it there
            $pos = array_search($key, $restrictTo);

            if ($pos > 0) {
                $prevKey = $restrictTo[$pos - 1];

                foreach ($sources as $i => $otherSource) {
                    if (($otherSource['key'] ?? null) === $prevKey) {
                        array_splice($sources, $i + 1, 0, [$source]);
                        $inserted = true;
                        break;
                    }
                }
            }

            if (! $inserted) {
                $sources[] = $source;
            }
        }

        return collect($sources);
    }

    /**
     * Whether the sidebar is worth showing: there are at least two selectable
     * (non-heading) sources, or a single one with nested sources under it.
     *
     * @param  iterable<int, SourceConfig>  $sources
     */
    public function showSidebar(iterable $sources): bool
    {
        $foundSource = false;

        foreach ($sources as $source) {
            if ($source['type'] !== ElementSources::TYPE_HEADING) {
                if ($foundSource || ! empty($source['nested'])) {
                    return true;
                }

                $foundSource = true;
            }
        }

        return false;
    }

    /**
     * The table columns a user can choose from, keyed by attribute name: the
     * element type's common attributes, plus the given source's own columns
     * when one is known. (The index shell resolves its source client-side, so
     * it only gets the common set.)
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return Collection<string, array<string, mixed>>
     */
    public function tableColumns(string $elementType, ?string $sourceKey = null): Collection
    {
        $columns = $this->elementSources->getAvailableTableAttributes($elementType);

        if ($sourceKey !== null) {
            $columns = $columns->merge($this->elementSources->getSourceTableAttributes($elementType, $sourceKey));
        }

        return $columns;
    }

    /**
     * The element type's sort options, normalized.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return Collection<int, NormalizedSortOption>
     */
    public function sortOptions(string $elementType): Collection
    {
        return collect($elementType::sortOptions())
            ->map(fn (mixed $option, string|int $key): array => $this->normalizeSortOption($option, $key))
            ->values();
    }

    /**
     * Normalizes one sort option into `key`/`label`/`attribute`/`defaultDir`.
     *
     * A sort option is either a bare label (keyed by the attribute it sorts on)
     * or an array with some combination of `label`, `attribute`, `orderBy` and
     * `defaultDir`. `attribute` is left as-is when it resolves to an `orderBy`
     * that isn't a string — a query expression or closure sorts fine but can't
     * be addressed from the client, so callers that need an addressable name
     * filter on `is_string()`.
     *
     * `label` is only set when the option declares one; the raw `option` comes
     * along so callers can keep their own fallback.
     *
     * @return NormalizedSortOption
     */
    public function normalizeSortOption(mixed $option, string|int $key = ''): array
    {
        if (! is_array($option)) {
            return [
                'key' => $key,
                'label' => $option,
                'attribute' => (string) $key,
                'defaultDir' => 'asc',
                'option' => $option,
            ];
        }

        return [
            'key' => $key,
            'label' => $option['label'] ?? null,
            'attribute' => $option['attribute'] ?? $option['orderBy'] ?? null,
            'defaultDir' => $option['defaultDir'] ?? 'asc',
            'option' => $option,
        ];
    }

    /**
     * Resolves a `showSiteMenu` setting, where `'auto'` means "show it if the
     * element type is localized".
     *
     * Callers that only ever offer the menu on multi-site installs gate this
     * behind {@see Sites::isMultiSite()} themselves.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function showSiteMenu(string $elementType, mixed $setting = 'auto'): bool
    {
        return $setting === 'auto'
            ? $elementType::isLocalized()
            : (bool) $setting;
    }

    /**
     * Resolves a `showStatusMenu` setting, where `'auto'` means "show it if the
     * element type has statuses worth filtering on" — a single status is no
     * choice at all, and status filtering is a no-op for types without any.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function showStatusMenu(string $elementType, mixed $setting = 'auto'): bool
    {
        return $setting === 'auto'
            ? ($elementType::hasStatuses() && count($elementType::statuses()) >= 2)
            : (bool) $setting;
    }
}
