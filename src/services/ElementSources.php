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
     * Returns the element index sources in the custom groupings/order.
     *
     * @param class-string<ElementInterface> $elementType The element type class
     * @param string $context The context
     * @param bool $withDisabled Whether disabled sources should be included
     * @return array[]
     */
    public function getSources(string $elementType, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): array
    {
        $nativeSources = $this->_nativeSources($elementType, $context);
        $sourceConfigs = $this->_sourceConfigs($elementType);

        if (!empty($sourceConfigs)) {
            // Merge native source settings into the configs
            $sources = [];
            $indexedNativeSources = ArrayHelper::index(array_filter($nativeSources, fn($s) => $s['type'] === self::TYPE_NATIVE), 'key');
            $nativeSourceKeys = [];
            foreach ($sourceConfigs as $source) {
                if ($source['type'] === self::TYPE_NATIVE) {
                    if (isset($indexedNativeSources[$source['key']])) {
                        if ($withDisabled || !($source['disabled'] ?? false)) {
                            $sources[] = $source + $indexedNativeSources[$source['key']];
                            $nativeSourceKeys[$source['key']] = true;
                        } else {
                            unset($indexedNativeSources[$source['key']]);
                        }
                    }
                } else {
                    if ($source['type'] === self::TYPE_CUSTOM) {
                        if ($context === self::CONTEXT_INDEX && !$this->_showCustomSource($source)) {
                            continue;
                        }
                        $source = $elementType::modifyCustomSource($source);
                        if (!$withDisabled && ($source['disabled'] ?? false)) {
                            continue;
                        }
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
                if (!empty($sources)) {
                    $sources[] = [
                        'type' => self::TYPE_HEADING,
                        'heading' => '',
                    ];
                }
                array_push($sources, ...$missingSources);
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
     * @return bool
     * @since 5.7.11
     */
    public function sourceExists(string $elementType, string $sourceKey, string $context = self::CONTEXT_INDEX, bool $withDisabled = false): bool
    {
        foreach ($this->getSources($elementType, $context, $withDisabled) as $source) {
            if (($source['key'] ?? null) === $sourceKey) {
                return true;
            }
        }

        return false;
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
        // options and each option has a string orderBy value, cmobine them
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
                        $field instanceof PreviewableFieldInterface &&
                        (!$user || $user->admin || ($layoutElement->getUserCondition()?->matchElement($user) ?? true))
                    ) {
                        if ($layoutElement->handle === null) {
                            // The handle wasn't overridden, so combine it with any other instances (from other layouts)
                            // where the handle also wasn't overridden
                            $groupedFieldElements[$field->id][] = $layoutElement;
                        } else {
                            // The handle was overridden, so it gets its own table attribute
                            $attributes["fieldInstance:$layoutElement->uid"] = [
                                'label' => Craft::t('site', $layoutElement->label()),
                            ];
                        }
                    }
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
}
