<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use Craft;
use craft\base\conditions\ConditionInterface;
use craft\base\ElementInterface;
use craft\db\CoalesceColumnsExpression;
use craft\elements\conditions\ElementConditionInterface;
use craft\errors\FieldNotFoundException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Cp;
use craft\models\FieldLayout;
use CraftCms\Cms\Element\Events\DefineSourceSortOptions;
use CraftCms\Cms\Element\Events\DefineSourceTableAttributes;
use CraftCms\Cms\Field\Contracts\PreviewableFieldInterface;
use CraftCms\Cms\Field\Contracts\SortableFieldInterface;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\t;

#[Singleton]
final class ElementSources
{
    public const string TYPE_HEADING = 'heading';

    public const string TYPE_NATIVE = 'native';

    public const string TYPE_CUSTOM = 'custom';

    public const string CONTEXT_FIELD = 'field';

    public const string CONTEXT_INDEX = 'index';

    public const string CONTEXT_MODAL = 'modal';

    public const string CONTEXT_SETTINGS = 'settings';

    /**
     * @see defineSources()
     */
    private array $sources = [];

    /**
     * @see getFieldLayoutsForSource()
     */
    private array $fieldLayouts;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
    ) {}

    /**
     * Filters out any unnecessary headings from a given source list.
     *
     * @param  array[]|Collection<array>  $sources
     * @return Collection<array>
     */
    public static function filterExtraHeadings(array|Collection $sources): Collection
    {
        return collect($sources)
            ->filter(function (array $source, int $i) use ($sources) {
                if ($source['type'] !== self::TYPE_HEADING) {
                    return true;
                }

                if (! isset($sources[$i + 1])) {
                    return false;
                }

                return $sources[$i + 1]['type'] !== self::TYPE_HEADING;
            })
            ->values();
    }

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $context  The context
     * @param  bool  $withDisabled  Whether disabled sources should be included
     * @param  string|null  $page  The page to fetch sources for
     * @return Collection<array>
     */
    public function getSources(
        string $elementType,
        string $context = self::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): Collection {
        $sources = $this
            ->sources($elementType, $context)
            ->when(
                ! $withDisabled,
                fn (Collection $sources) => $sources->reject(fn (array $source): bool => (bool) ($source['disabled'] ?? false))
            );

        if ($page && isset($sources[0]['page'])) {
            $pageNameId = $this->pageNameId($page);
            $sources = $sources->filter(fn (array $source) => (
                isset($source['page']) &&
                $this->pageNameId($source['page']) === $pageNameId
            ));
        }

        return $sources->values();
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return Collection<array>
     */
    private function sources(string $elementType, string $context): Collection
    {
        if (! isset($this->sources[$elementType][$context])) {
            $this->sources[$elementType][$context] = $this->defineSources($elementType, $context);
        }

        return collect($this->sources[$elementType][$context]);
    }

    /**
     * @param  class-string<ElementInterface>  $elementType
     * @return array[]
     */
    private function defineSources(string $elementType, string $context): array
    {
        $nativeSources = $this->nativeSources($elementType, $context);
        $sourceConfigs = $this->sourceConfigs($elementType);

        if (! empty($sourceConfigs)) {
            // Merge native source settings into the configs
            $sources = [];
            $nativeSourceKeys = [];
            $indexedNativeSources = collect($nativeSources)
                ->where('type', self::TYPE_NATIVE)
                ->keyBy('key')
                ->all();

            $firstPage = $sourceConfigs[0]['page'] ?? null;

            foreach ($sourceConfigs as $source) {
                if ($source['type'] === self::TYPE_NATIVE) {
                    if (isset($indexedNativeSources[$source['key']])) {
                        $sources[] = $source + $indexedNativeSources[$source['key']];
                        $nativeSourceKeys[$source['key']] = true;
                    }

                    continue;
                }

                if ($source['type'] === self::TYPE_CUSTOM) {
                    if ($context === self::CONTEXT_INDEX && ! $this->showCustomSource($source)) {
                        continue;
                    }

                    $source = $elementType::modifyCustomSource($source);
                }

                $sources[] = $source;
            }

            // Make sure all native sources are accounted for
            $missingSources = array_filter($nativeSources, fn ($s) => (
                $s['type'] === self::TYPE_NATIVE &&
                isset($indexedNativeSources[$s['key']]) &&
                ! isset($nativeSourceKeys[$s['key']])
            ));

            if (! empty($missingSources)) {
                // If there are any headings, add a blank heading
                if (Arr::first($sources, fn (array $source) => $source['type'] === self::TYPE_HEADING)) {
                    $sources[] = [
                        'type' => self::TYPE_HEADING,
                        'heading' => '',
                        'page' => $firstPage,
                    ];
                }

                array_push($sources, ...array_map(fn (array $source) => [
                    ...$source,
                    'page' => $firstPage,
                ], $missingSources));
            }
        } else {
            $sources = $nativeSources;
        }

        // Normalize the site IDs
        foreach ($sources as &$source) {
            if (! isset($source['sites'])) {
                continue;
            }

            $source['sites'] = collect($source['sites'] ?? [])
                ->map(function (int|string $siteId) {
                    if (! is_string($siteId)) {
                        return $siteId;
                    }

                    if (! Str::isUuid($siteId)) {
                        return (int) $siteId;
                    }

                    try {
                        return Sites::getSiteByUid($siteId)->id;
                    } catch (SiteNotFoundException) {
                        return null;
                    }
                })
                ->filter()
                ->all();
        }

        return $sources;
    }

    /**
     * Returns whether the given source exists.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $sourceKey  The source key
     * @param  string  $context  The context
     * @param  bool  $withDisabled  Whether disabled sources should be included
     * @param  string|null  $page  The page to fetch sources for
     */
    public function sourceExists(
        string $elementType,
        string $sourceKey,
        string $context = self::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): bool {
        return $this->getSources($elementType, $context, $withDisabled, $page)->contains(
            fn (array $source) => ($source['key'] ?? null) === $sourceKey,
        );
    }

    /**
     * Returns the unique pages found for the given element type’s sources.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $context  The context
     * @param  bool  $withDisabled  Whether disabled sources should be included
     * @return Collection<string>
     */
    public function getPages(string $elementType, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): Collection
    {
        return $this->getSources($elementType, $context, $withDisabled)
            ->map(fn (array $source) => $source['page'] ?? null)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Returns the first page found for the given element type’s sources.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $context  The context
     * @param  bool  $withDisabled  Whether disabled sources should be included
     */
    public function getFirstPage(string $elementType, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): ?string
    {
        foreach ($this->getSources($elementType, $context, $withDisabled) as $source) {
            if (isset($source['page'])) {
                return $source['page'];
            }
        }

        return null;
    }

    /**
     * Returns whether the given page exists for an element type.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $context  The context
     * @param  bool  $withDisabled  Whether disabled sources should be included
     */
    public function pageExists(string $elementType, string $page, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): bool
    {
        $nameId = $this->pageNameId($page);

        return $this->getSources($elementType, $context, $withDisabled)
            ->contains(fn (array $source) => isset($source['page']) && $nameId === $this->pageNameId($source['page']));
    }

    /**
     * Returns a normalized ID for a given page name.
     */
    public function pageNameId(string $page): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}\p{M}]/u', '', $page));
    }

    /**
     * Returns whether the given custom source should be available for the current user.
     */
    private function showCustomSource(array $source): bool
    {
        if (! isset($source['userGroups'])) {
            // Show for everyone
            return true;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($source['userGroups'] === false) {
            return false;
        }

        return array_any(
            $user->getGroups(),
            fn ($group) => in_array($group->uid, $source['userGroups'], true)
        );
    }

    /**
     * Returns the common table attributes that are available for a given element type, across all its sources.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @return Collection<array>
     */
    public function getAvailableTableAttributes(string $elementType): Collection
    {
        $attributes = $elementType::tableAttributes();

        // Normalize
        foreach ($attributes as $key => $info) {
            if (! is_array($info)) {
                $attributes[$key] = ['label' => $info];
            } elseif (! isset($info['label'])) {
                $attributes[$key]['label'] = '';
            }

            if (isset($attributes[$key]['icon']) && in_array($attributes[$key]['icon'], ['world', 'earth'])) {
                $attributes[$key]['icon'] = Cp::earthIcon();
            }
        }

        return collect($attributes);
    }

    /**
     * Returns the attributes that should be shown for a given element type source.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $sourceKey  The element type source key
     * @param  string[]|null  $customAttributes  Custom attributes to show rather than the defaults
     * @return Collection<array>
     */
    public function getTableAttributes(string $elementType, string $sourceKey, ?array $customAttributes = null): Collection
    {
        // If this is a source path, use the first segment
        if (($slash = strpos($sourceKey, '/')) !== false) {
            $sourceKey = substr($sourceKey, 0, $slash);
        }

        if ($sourceKey === '__IMP__') {
            $sourceAttributes = $this->getTableAttributesForFieldLayouts($elementType::fieldLayouts(null));
        } else {
            $sourceAttributes = $this->getSourceTableAttributes($elementType, $sourceKey);
        }

        $availableAttributes = $this->getAvailableTableAttributes($elementType)->merge($sourceAttributes);

        $attributeKeys = $customAttributes
            ?? $this->sourceConfig($elementType, $sourceKey)['tableAttributes']
            ?? $elementType::defaultTableAttributes($sourceKey);

        $attributes = [
            // Start with the element type’s display name
            ['title', ['label' => $elementType::displayName()]],
        ];

        if (is_array($attributeKeys)) {
            foreach ($attributeKeys as $key) {
                if (isset($availableAttributes[$key])) {
                    $attributes[] = [$key, $availableAttributes[$key]];
                }
            }
        }

        return collect($attributes);
    }

    /**
     * Returns all the field layouts available for the given element source.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return Collection<FieldLayout>
     */
    public function getFieldLayoutsForSource(string $elementType, string $sourceKey): Collection
    {
        if (isset($this->fieldLayouts[$elementType][$sourceKey])) {
            return collect($this->fieldLayouts[$elementType][$sourceKey]);
        }

        if (! str_starts_with($sourceKey, 'custom:')) {
            return collect($this->fieldLayouts[$elementType][$sourceKey] = $elementType::fieldLayouts($sourceKey));
        }

        // Don't bother the element type for custom sources
        $source = $this->sourceConfig($elementType, $sourceKey);

        if (empty($source['condition'])) {
            return Fields::getLayoutsByType($elementType);
        }

        /** @var ElementConditionInterface $condition */
        $condition = Craft::$app->getConditions()->createCondition($source['condition']);
        $query = $elementType::find();
        $condition->modifyQuery($query);

        return collect($this->fieldLayouts[$elementType][$sourceKey] = $query->getFieldLayouts());
    }

    /**
     * Returns additional sort options that should be available for a given element source.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $sourceKey  The element source key
     * @return Collection<array>
     */
    public function getSourceSortOptions(string $elementType, string $sourceKey): Collection
    {
        $fieldLayouts = $sourceKey === '__IMP__'
            ? $elementType::fieldLayouts(null)
            : $this->getFieldLayoutsForSource($elementType, $sourceKey);

        $sortOptions = $this->getSortOptionsForFieldLayouts($fieldLayouts);

        if (Event::hasListeners(DefineSourceSortOptions::class)) {
            Event::dispatch($event = new DefineSourceSortOptions(
                elementType: $elementType,
                source: $sourceKey,
                sortOptions: $sortOptions,
            ));

            $sortOptions = $event->sortOptions;
        }

        // Combine duplicate attributes. If any attributes map to multiple sort
        // options and each option has a string orderBy value, cmobine them
        // with a CoalesceColumnsExpression.
        return $sortOptions
            ->groupBy('attribute')
            ->map(function (Collection $group) {
                $orderBys = $group->pluck('orderBy');

                if ($orderBys->count() === 1 || $orderBys->doesntContain(fn ($orderBy) => is_string($orderBy))) {
                    return $group->first();
                }

                $expression = new CoalesceColumnsExpression($orderBys->all());

                return array_merge($group->first(), [
                    'orderBy' => $expression,
                ]);
            });
    }

    /**
     * Returns additional sort options that should be available for an element index source that includes the given
     * field layouts.
     *
     * @param  FieldLayout[]|Collection<FieldLayout>  $fieldLayouts
     * @return Collection<array>
     */
    public function getSortOptionsForFieldLayouts(array|Collection $fieldLayouts): Collection
    {
        $sortOptions = [];

        foreach ($fieldLayouts as $fieldLayout) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();

                if (! $field instanceof SortableFieldInterface) {
                    continue;
                }

                $sortOption = $field->getSortOption();
                $sortOption['attribute'] ??= $sortOption['orderBy'];
                $sortOption['defaultDir'] ??= 'asc';
                $sortOptions[] = $sortOption;
            }
        }

        return collect($sortOptions);
    }

    /**
     * Returns any table attributes that should be available for a given source, in addition to the [[getAvailableTableAttributes()|common attributes]].
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @param  string  $sourceKey  The element source key
     * @return Collection<array>
     */
    public function getSourceTableAttributes(string $elementType, string $sourceKey): Collection
    {
        if ($sourceKey === '__IMP__') {
            return collect();
        }

        $fieldLayouts = $this->getFieldLayoutsForSource($elementType, $sourceKey);
        $attributes = $this->getTableAttributesForFieldLayouts($fieldLayouts);

        if (Event::hasListeners(DefineSourceTableAttributes::class)) {
            Event::dispatch($event = new DefineSourceTableAttributes(
                elementType: $elementType,
                source: $sourceKey,
                attributes: $attributes,
            ));

            return $event->attributes;
        }

        return $attributes;
    }

    /**
     * Returns any table attributes that should be available for an element index source that includes the given
     * field layouts.
     *
     * @param  FieldLayout[]|Collection<FieldLayout>  $fieldLayouts
     * @return Collection<array>
     */
    public function getTableAttributesForFieldLayouts(array|Collection $fieldLayouts): Collection
    {
        $user = Auth::user();
        $userElement = Craft::$app->getUsers()->getUserById($user->id);

        $attributes = [];
        /** @var CustomField[][] $groupedFieldElements */
        $groupedFieldElements = [];

        foreach ($fieldLayouts as $fieldLayout) {
            foreach ($fieldLayout->getTabs() as $tab) {
                // Factor in the user condition for non-admins
                if ($user && ! $user->isAdmin() && ! ($tab->getUserCondition()?->matchElement($userElement) ?? true)) {
                    continue;
                }

                foreach ($tab->getElements() as $layoutElement) {
                    if (! $layoutElement instanceof CustomField) {
                        continue;
                    }

                    try {
                        $field = $layoutElement->getField();
                    } catch (FieldNotFoundException) {
                        continue;
                    }

                    if (
                        $field instanceof PreviewableFieldInterface &&
                        (! $user || $user->isAdmin() || ($layoutElement->getUserCondition()?->matchElement($userElement) ?? true))
                    ) {
                        if ($layoutElement->handle === null) {
                            // The handle wasn't overridden, so combine it with any other instances (from other layouts)
                            // where the handle also wasn't overridden
                            $groupedFieldElements[$field->id][] = $layoutElement;
                        } else {
                            // The handle was overridden, so it gets its own table attribute
                            $attributes["fieldInstance:$layoutElement->uid"] = [
                                'label' => t($layoutElement->label(), category: 'site'),
                            ];
                        }
                    }
                }
            }
        }

        foreach ($groupedFieldElements as $fieldElements) {
            $field = $fieldElements[0]->getField();
            $labels = array_unique(array_map(fn (CustomField $layoutElement) => $layoutElement->label(), $fieldElements));
            $attributes["field:$field->uid"] = [
                'label' => count($labels) === 1 ? $labels[0] : t($field->name, category: 'site'),
            ];
        }

        return collect($attributes);
    }

    /**
     * Returns the native sources for a given element type and context, normalized with `type` keys.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @return array[]
     */
    private function nativeSources(string $elementType, string $context): array
    {
        $sources = $elementType::sources($context);
        $normalized = [];

        foreach ($sources as $source) {
            if (! isset($source['type'])) {
                if (array_key_exists('heading', $source)) {
                    $source['type'] = self::TYPE_HEADING;
                } elseif (isset($source['key'])) {
                    $source['type'] = self::TYPE_NATIVE;
                } else {
                    continue;
                }
            }

            $this->normalizeNativeSource($source);
            $normalized[] = $source;
        }

        return $normalized;
    }

    private function normalizeNativeSource(array &$source): void
    {
        if (isset($source['defaultFilter']) && $source['defaultFilter'] instanceof ConditionInterface) {
            $source['defaultFilter'] = $source['defaultFilter']->getConfig();
        }

        if (isset($source['nested'])) {
            foreach ($source['nested'] as &$nested) {
                $this->normalizeNativeSource($nested);
            }
        }
    }

    /**
     * Returns the source configs for a given element type.
     *
     * @param  class-string<ElementInterface>  $elementType  The element type class
     * @return array[]|null
     */
    private function sourceConfigs(string $elementType): ?array
    {
        return $this->projectConfig->get(ProjectConfig::PATH_ELEMENT_SOURCES.".$elementType");
    }

    /**
     * Returns the source config for a given native source key.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    private function sourceConfig(string $elementType, string $sourceKey): ?array
    {
        $sourceConfigs = $this->sourceConfigs($elementType);
        if (empty($sourceConfigs)) {
            return null;
        }

        return Arr::first(
            $sourceConfigs,
            fn ($s) => $s['type'] !== self::TYPE_HEADING && $s['key'] === $sourceKey
        );
    }

    /**
     * Returns the page settings for a given element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     */
    public function getPageSettings(string $elementType): array
    {
        return $this->projectConfig->get(
            sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, $elementType)
        ) ?? [];
    }
}
