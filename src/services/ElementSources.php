<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\conditions\ConditionInterface;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\db\CoalesceColumnsExpression;
use craft\elements\conditions\ElementConditionInterface;
use craft\errors\FieldNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\events\DefineSourceSortOptionsEvent;
use craft\events\DefineSourceTableAttributesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\fields\ContentBlock;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use Illuminate\Support\Collection;
use yii\base\Component;

/**
 * The Element Sources service provides APIs for managing element indexes.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getElementSources()|`Craft::$app->getElementSources()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ElementSources extends Component
{
    /**
     * @event DefineSourceTableAttributesEvent The event that is triggered when defining the available table attributes for a source.
     */
    public const EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES = 'defineSourceTableAttributes';

    /**
     * @event DefineSourceSortOptionsEvent The event that is triggered when defining the available sort options for a source.
     */
    public const EVENT_DEFINE_SOURCE_SORT_OPTIONS = 'defineSourceSortOptions';

    public const TYPE_HEADING = 'heading';
    public const TYPE_NATIVE = 'native';
    public const TYPE_CUSTOM = 'custom';

    public const CONTEXT_FIELD = 'field';
    public const CONTEXT_INDEX = 'index';
    public const CONTEXT_MODAL = 'modal';
    public const CONTEXT_SETTINGS = 'settings';

    /**
     * Filters out any unnecessary headings from a given source list.
     *
     * @param array[] $sources
     * @return array[]
     */
    public static function filterExtraHeadings(array $sources): array
    {
        return array_values(array_filter($sources, fn($source, $i) => $source['type'] !== self::TYPE_HEADING ||
        (isset($sources[$i + 1]) && $sources[$i + 1]['type'] !== self::TYPE_HEADING), ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @see defineSources()
     */
    private array $sources = [];

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @param string|null $page The page to fetch sources for
     * @return array[]
     */
    public function getSources(
        string $elementType,
        string $context = self::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): array {
        $sources = $this->sources($elementType, $context);

        if (!$withDisabled) {
            $sources = array_filter($sources, fn(array $source) => !($source['disabled'] ?? false));
        }

        if (
            $page &&
            isset($sources[0]['page']) &&
            // ignore if there's only one page and it has a blank name; otherwise there's no way to fix
            // (https://github.com/craftcms/cms/issues/18321)
            ($sources[0]['page'] !== '' || count($this->getPages($elementType, $context)) !== 1)
        ) {
            $pageNameId = $this->pageNameId($page);
            $sources = array_filter($sources, fn(array $source) => (
                isset($source['page']) &&
                $this->pageNameId($source['page']) === $pageNameId
            ));
        }

        return array_values($sources);
    }

    /**
     * @param class-string<ElementInterface> $elementType
     * @param string $context
     * @return array[]
     */
    private function sources(string $elementType, string $context): array
    {
        if (!isset($this->sources[$elementType][$context])) {
            $this->sources[$elementType][$context] = $this->defineSources($elementType, $context);
        }

        return $this->sources[$elementType][$context];
    }

    /**
     * @param class-string<ElementInterface> $elementType
     * @param string $context
     * @return array[]
     */
    private function defineSources(string $elementType, string $context): array
    {
        $nativeSources = $this->_nativeSources($elementType, $context);
        $sourceConfigs = $this->_sourceConfigs($elementType);

        if (!empty($sourceConfigs)) {
            // Merge native source settings into the configs
            $sources = [];
            $indexedNativeSources = ArrayHelper::index(array_filter($nativeSources, fn($s) => $s['type'] === self::TYPE_NATIVE), 'key');
            $nativeSourceKeys = [];

            $firstPage = $sourceConfigs[0]['page'] ?? null;

            foreach ($sourceConfigs as $source) {
                if ($source['type'] === self::TYPE_NATIVE) {
                    if (isset($indexedNativeSources[$source['key']])) {
                        $sources[] = $source + $indexedNativeSources[$source['key']];
                        $nativeSourceKeys[$source['key']] = true;
                    }
                } else {
                    if ($source['type'] === self::TYPE_CUSTOM) {
                        if ($context === self::CONTEXT_INDEX && !$this->_showCustomSource($source)) {
                            continue;
                        }
                        $source = $elementType::modifyCustomSource($source);
                    }
                    $sources[] = $source;
                }
            }

            // Make sure all native sources are accounted for
            $missingSources = array_filter($nativeSources, fn($s) => (
                $s['type'] === self::TYPE_NATIVE &&
                isset($indexedNativeSources[$s['key']]) &&
                !isset($nativeSourceKeys[$s['key']])
            ));

            if (!empty($missingSources)) {
                // If there are any headings, add a blank heading
                if (ArrayHelper::contains($sources, fn(array $source) => $source['type'] === self::TYPE_HEADING)) {
                    $sources[] = [
                        'type' => self::TYPE_HEADING,
                        'heading' => '',
                        'page' => $firstPage,
                    ];
                }

                array_push($sources, ...array_map(fn(array $source) => [
                    ...$source,
                    'page' => $firstPage,
                ], $missingSources));
            }
        } else {
            $sources = $nativeSources;
        }

        // Normalize the site IDs
        foreach ($sources as &$source) {
            if (isset($source['sites'])) {
                $sitesService = null;
                $source['sites'] = array_filter(array_map(function(int|string $siteId) use (&$sitesService): ?int {
                    if (is_string($siteId) && StringHelper::isUUID($siteId)) {
                        $sitesService ??= Craft::$app->getSites();
                        try {
                            return $sitesService->getSiteByUid($siteId)->id;
                        } catch (SiteNotFoundException) {
                            return null;
                        }
                    }
                    return (int)$siteId;
                }, $source['sites'] ?: []));
            }
        }

        return $sources;
    }

    /**
     * Returns whether the given source exists.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The source key
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @param string|null $page The page to fetch sources for
     * @return bool
     * @since 5.7.11
     */
    public function sourceExists(
        string $elementType,
        string $sourceKey,
        string $context = self::CONTEXT_INDEX,
        bool $withDisabled = false,
        ?string $page = null,
    ): bool {
        foreach ($this->getSources($elementType, $context, $withDisabled, $page) as $source) {
            if (($source['key'] ?? null) === $sourceKey) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the unique pages found for the given element type’s sources.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return string[]
     * @since 5.9.0
     */
    public function getPages(string $elementType, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): array
    {
        $pages = [];

        foreach ($this->_sourceConfigs($elementType) ?? [] as $source) {
            // divide all sources into pages
            if (isset($source['page'])) {
                $pages[$source['page']][] = $source;
            }
        }

        // Remove pages that only have disabled sources
        $pages = array_filter(
            $pages,
            fn(array $sources) => ArrayHelper::contains($sources, fn(array $source) => !($source['disabled'] ?? false)),
        );

        return array_keys($pages);
    }

    /**
     * Returns the first page found for the given element type’s sources.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return string|null
     * @since 5.9.0
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
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return bool
     * @since 5.9.0
     */
    public function pageExists(string $elementType, string $page, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): bool
    {
        $nameId = $this->pageNameId($page);
        foreach ($this->getSources($elementType, $context, $withDisabled) as $source) {
            if (isset($source['page']) && $nameId === $this->pageNameId($source['page'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a normalized ID for a given page name.
     *
     * @param string $page
     * @return string
     * @since 5.9.0
     */
    public function pageNameId(string $page): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}\p{M}]/u', '', $page));
    }

    /**
     * Returns whether the given custom source should be available for the current user.
     *
     * @param array $source
     * @return bool
     */
    private function _showCustomSource(array $source): bool
    {
        if (!isset($source['userGroups'])) {
            // Show for everyone
            return true;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return false;
        }

        if ($user->admin) {
            return true;
        }

        if ($source['userGroups'] === false) {
            return false;
        }

        foreach ($user->getGroups() as $group) {
            if (in_array($group->uid, $source['userGroups'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves an element’s source configs.
     *
     * @param class-string<ElementInterface> $elementType
     * @param array $sources
     * @since 5.9.0
     */
    public function saveSources(string $elementType, array $sources): void
    {
        // config cleanup
        $sources = array_map(fn(array $s) => array_filter([
            'type' => $s['type'] ?? self::TYPE_NATIVE,
            'key' => $s['key'] ?? null,
            'page' => $s['page'] ?? null,
            'tableAttributes' => $s['tableAttributes'] ?? null,
            'defaultSort' => $s['defaultSort'] ?? null,
            'defaultViewMode' => $s['defaultViewMode'] ?? null,
            ...match ($s['type'] ?? self::TYPE_NATIVE) {
                self::TYPE_CUSTOM => [
                    'label' => $s['label'] ?? null,
                    'condition' => ($s['condition'] ?? false)
                        ? ($s['condition'] instanceof ConditionInterface ? $s['condition']->getConfig() : $s['condition'])
                        : null,
                    'sites' => $s['sites'] ?? null,
                    'userGroups' => $s['userGroups'] ?? null,
                ],
                self::TYPE_HEADING => [
                    'heading' => $s['heading'] ?? null,
                ],
                default => [
                    'disabled' => $s['disabled'] ?? null,
                ],
            },
        ], fn($val) => $val !== null), $sources);

        $path = sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCES, $elementType);
        Craft::$app->getProjectConfig()->set($path, $sources);
    }

    /**
     * Returns the common table attributes that are available for a given element type, across all its sources.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @return array[]
     */
    public function getAvailableTableAttributes(string $elementType): array
    {
        $attributes = $elementType::tableAttributes();

        // Normalize
        foreach ($attributes as $key => $info) {
            if (!is_array($info)) {
                $attributes[$key] = ['label' => $info];
            } elseif (!isset($info['label'])) {
                $attributes[$key]['label'] = '';
            }

            if (isset($attributes[$key]['icon']) && in_array($attributes[$key]['icon'], ['world', 'earth'])) {
                $attributes[$key]['icon'] = Cp::earthIcon();
            }
        }

        return $attributes;
    }

    /**
     * Returns the attributes that should be shown for a given element type source.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The element type source key
     * @param string[]|null $customAttributes Custom attributes to show rather than the defaults
     * @return array[]
     */
    public function getTableAttributes(string $elementType, string $sourceKey, ?array $customAttributes = null): array
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

        $availableAttributes = array_merge(
            $this->getAvailableTableAttributes($elementType),
            $sourceAttributes,
        );

        $attributeKeys = $customAttributes
            ?? $this->_sourceConfig($elementType, $sourceKey)['tableAttributes']
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

        return $attributes;
    }

    /**
     * @var array
     * @see getFieldLayoutsForSource()
     */
    private array $_fieldLayouts;

    /**
     * Returns all the field layouts available for the given element source.
     *
     * @param class-string<ElementInterface> $elementType
     * @param string $sourceKey
     * @return FieldLayout[]
     */
    public function getFieldLayoutsForSource(string $elementType, string $sourceKey): array
    {
        if (!isset($this->_fieldLayouts[$elementType][$sourceKey])) {
            // Don't bother the element type for custom sources
            if (str_starts_with($sourceKey, 'custom:')) {
                $source = $this->_sourceConfig($elementType, $sourceKey);
                if (empty($source['condition'])) {
                    return Craft::$app->getFields()->getLayoutsByType($elementType);
                }
                /** @var ElementConditionInterface $condition */
                $condition = Craft::$app->getConditions()->createCondition($source['condition']);
                $query = $elementType::find();
                $condition->modifyQuery($query);
                $this->_fieldLayouts[$elementType][$sourceKey] = $query->getFieldLayouts();
            } else {
                $this->_fieldLayouts[$elementType][$sourceKey] = $elementType::fieldLayouts($sourceKey);
            }
        }

        return $this->_fieldLayouts[$elementType][$sourceKey];
    }

    /**
     * Returns additional sort options that should be available for a given element source.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The element source key
     * @return array[]
     */
    public function getSourceSortOptions(string $elementType, string $sourceKey): array
    {
        $fieldLayouts = $sourceKey === '__IMP__'
            ? $elementType::fieldLayouts(null)
            : $this->getFieldLayoutsForSource($elementType, $sourceKey);
        $sortOptions = $this->getSortOptionsForFieldLayouts($fieldLayouts);

        // Fire a 'defineSourceSortOptions' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_SOURCE_SORT_OPTIONS)) {
            $event = new DefineSourceSortOptionsEvent([
                'elementType' => $elementType,
                'source' => $sourceKey,
                'sortOptions' => $sortOptions,
            ]);
            $this->trigger(self::EVENT_DEFINE_SOURCE_SORT_OPTIONS, $event);
            $sortOptions = $event->sortOptions;
        }

        // Combine duplicate attributes. If any attributes map to multiple sort
        // options and each option has a string orderBy value, combine them
        // with a CoalesceColumnsExpression.
        return Collection::make($sortOptions)
            ->groupBy('attribute')
            ->map(function(Collection $group) {
                $orderBys = $group->pluck('orderBy');
                if ($orderBys->count() === 1 || $orderBys->doesntContain(fn($orderBy) => is_string($orderBy))) {
                    return $group->first();
                }
                $expression = new CoalesceColumnsExpression($orderBys->all());
                return array_merge($group->first(), [
                    'orderBy' => $expression,
                ]);
            })
            ->all();
    }

    /**
     * Returns additional sort options that should be available for an element index source that includes the given
     * field layouts.
     *
     * @param FieldLayout[] $fieldLayouts
     * @return array[]
     * @since 5.0.0
     */
    public function getSortOptionsForFieldLayouts(array $fieldLayouts): array
    {
        $sortOptions = [];
        $qb = null;

        foreach ($fieldLayouts as $fieldLayout) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();
                if ($field instanceof SortableFieldInterface) {
                    $sortOption = $field->getSortOption();
                    if (!isset($sortOption['attribute'])) {
                        $sortOption['attribute'] = $sortOption['orderBy'];
                    }
                    if (!isset($sortOption['defaultDir'])) {
                        $sortOption['defaultDir'] = 'asc';
                    }
                    $sortOptions[] = $sortOption;
                }
            }

            foreach ($fieldLayout->getGeneratedFields() as $field) {
                if (($field['name'] ?? '') !== '') {
                    $qb ??= Craft::$app->getDb()->getQueryBuilder();
                    $sortOptions[] = [
                        'label' => Craft::t('site', $field['name']),
                        'attribute' => "generatedField:{$field['uid']}",
                        'orderBy' => $qb->jsonExtract('elements_sites.content', [$field['uid']]),
                        'defaultDir' => 'asc',
                    ];
                }
            }
        }

        return $sortOptions;
    }

    /**
     * Returns any table attributes that should be available for a given source, in addition to the [[getAvailableTableAttributes()|common attributes]].
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $sourceKey The element source key
     * @return array[]
     */
    public function getSourceTableAttributes(string $elementType, string $sourceKey): array
    {
        if ($sourceKey === '__IMP__') {
            return [];
        }

        $fieldLayouts = $this->getFieldLayoutsForSource($elementType, $sourceKey);
        $attributes = $this->getTableAttributesForFieldLayouts($fieldLayouts);

        // Fire a 'defineSourceTableAttributes' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES)) {
            $event = new DefineSourceTableAttributesEvent([
                'elementType' => $elementType,
                'source' => $sourceKey,
                'attributes' => $attributes,
            ]);
            $this->trigger(self::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES, $event);
            return $event->attributes;
        }

        return $attributes;
    }

    /**
     * Returns any table attributes that should be available for an element index source that includes the given
     * field layouts.
     *
     * @param FieldLayout[] $fieldLayouts
     * @return array[]
     * @since 5.0.0
     */
    public function getTableAttributesForFieldLayouts(array $fieldLayouts): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        $attributes = [];
        /** @var CustomField[][] $groupedFieldElements */
        $groupedFieldElements = [];
        $groupedFieldInstances = [];

        foreach ($fieldLayouts as $fieldLayout) {
            foreach ($fieldLayout->getTabs() as $tab) {
                // Factor in the user condition for non-admins
                if ($user && !$user->admin && !($tab->getUserCondition()?->matchElement($user) ?? true)) {
                    continue;
                }

                foreach ($tab->getElements() as $layoutElement) {
                    if (!$layoutElement instanceof CustomField) {
                        continue;
                    }

                    try {
                        $field = $layoutElement->getField();
                    } catch (FieldNotFoundException) {
                        continue;
                    }

                    if (
                        ($field instanceof PreviewableFieldInterface || $field instanceof ContentBlock) &&
                        (!$user || $user->admin || ($layoutElement->getUserCondition()?->matchElement($user) ?? true))
                    ) {
                        if ($field instanceof ContentBlock) {
                            foreach ($this->getTableAttributesForFieldLayouts([$field->getFieldLayout()]) as $key => $attribute) {
                                $attributes["contentBlock:{$field->layoutElement->uid}.$key"] = $attribute;
                            }
                        } elseif ($layoutElement->handle === null) {
                            // The handle wasn't overridden, so combine it with any other instances (from other layouts)
                            // where the handle also wasn't overridden
                            $groupedFieldElements[$field->id][] = $layoutElement;
                        } else {
                            // The handle was overridden, so we'll use a key consisting of
                            // the global field uid and layout element label and handle
                            // to check if a new table attribute should be added to the list
                            $key = $field->uid . " - " . $layoutElement->label() . " - " . $layoutElement->handle;
                            if (!isset($groupedFieldInstances[$key])) {
                                $groupedFieldInstances[$key] = $layoutElement;
                            }
                        }
                    }
                }
            }

            foreach ($fieldLayout->getGeneratedFields() as $field) {
                if (($field['name'] ?? '') !== '') {
                    $attributes["generatedField:{$field['uid']}"] = [
                        'label' => Craft::t('site', $field['name']),
                    ];
                }
            }
        }

        foreach ($groupedFieldElements as $fieldElements) {
            $field = $fieldElements[0]->getField();
            $labels = array_unique(array_map(fn(CustomField $layoutElement) => $layoutElement->label(), $fieldElements));
            $attributes["field:$field->uid"] = [
                'label' => count($labels) === 1 ? $labels[0] : Craft::t('site', $field->name),
            ];
        }

        foreach ($groupedFieldInstances as $layoutElement) {
            $attributes["fieldInstance:$layoutElement->uid"] = [
                'label' => Craft::t('site', $layoutElement->label()),
            ];
        }

        return $attributes;
    }

    /**
     * Returns the native sources for a given element type and context, normalized with `type` keys.
     *
     * @param class-string<ElementInterface> $elementType
     * @param string $context
     * @return array[]
     */
    private function _nativeSources(string $elementType, string $context): array
    {
        $sources = $elementType::sources($context);
        $normalized = [];

        foreach ($sources as $source) {
            if (!isset($source['type'])) {
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
     * @param class-string<ElementInterface> $elementType The element type class
     * @return array[]|null
     */
    private function _sourceConfigs(string $elementType): ?array
    {
        return Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ELEMENT_SOURCES . ".$elementType");
    }

    /**
     * Returns the source config for a given native source key.
     *
     * @param class-string<ElementInterface> $elementType
     * @param string $sourceKey
     * @return array|null
     */
    private function _sourceConfig(string $elementType, string $sourceKey): ?array
    {
        $sourceConfigs = $this->_sourceConfigs($elementType);
        if (empty($sourceConfigs)) {
            return null;
        }
        return ArrayHelper::firstWhere($sourceConfigs, fn($s) => $s['type'] !== self::TYPE_HEADING && $s['key'] === $sourceKey);
    }

    /**
     * Returns the page settings for a given element type.
     *
     * @param class-string<ElementInterface> $elementType
     * @return array
     * @since 5.9.0
     */
    public function getPageSettings(string $elementType): array
    {
        $path = sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, $elementType);
        return Craft::$app->getProjectConfig()->get($path) ?? [];
    }

    /**
     * Saves the page settings for a given element type.
     *
     * @param class-string<ElementInterface> $elementType
     * @param array $pageSettings
     * @since 5.9.0
     */
    public function savePageSettings(string $elementType, array $pageSettings): void
    {
        $path = sprintf('%s.%s', ProjectConfig::PATH_ELEMENT_SOURCE_PAGES, $elementType);
        Craft::$app->getProjectConfig()->set($path, $pageSettings);
    }
}
