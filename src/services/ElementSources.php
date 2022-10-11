<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\events\DefineSourceSortOptionsEvent;
use craft\events\DefineSourceTableAttributesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;
use yii\base\Component;

/**
 * The Element Sources service provides APIs for managing element indexes.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getElementSources()|`Craft::$app->elementSources`]].
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
        return array_values(array_filter($sources, function($source, $i) use ($sources) {
            return (
                $source['type'] !== self::TYPE_HEADING ||
                (isset($sources[$i + 1]) && $sources[$i + 1]['type'] !== self::TYPE_HEADING)
            );
        }, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param string $elementType The element type class
     * @phpstan-param class-string<ElementInterface> $elementType
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
                    if ($source['type'] === self::TYPE_CUSTOM && !$this->_showCustomSource($source)) {
                        continue;
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

        // Clear out any unwanted headings and return
        return static::filterExtraHeadings($sources);
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
     * @param string $elementType The element type class
     * @phpstan-param class-string<ElementInterface> $elementType
     * @return array[]
     */
    public function getAvailableTableAttributes(string $elementType): array
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $attributes = $elementType::tableAttributes();

        // Normalize
        foreach ($attributes as $key => $info) {
            if (!is_array($info)) {
                $attributes[$key] = ['label' => $info];
            } elseif (!isset($info['label'])) {
                $attributes[$key]['label'] = '';
            }
        }

        return $attributes;
    }

    /**
     * Returns the attributes that should be shown for a given element type source.
     *
     * @param string $elementType The element type class
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $sourceKey The element type source key
     * @param string[]|null $customAttributes Custom attributes to show rather than the defaults
     * @return array[]
     */
    public function getTableAttributes(string $elementType, string $sourceKey, ?array $customAttributes = null): array
    {
        /** @var ElementInterface|string $elementType */
        // If this is a source path, use the first segment
        if (($slash = strpos($sourceKey, '/')) !== false) {
            $sourceKey = substr($sourceKey, 0, $slash);
        }

        $availableAttributes = array_merge(
            $this->getAvailableTableAttributes($elementType),
            $this->getSourceTableAttributes($elementType, $sourceKey)
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
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $sourceKey
     * @return FieldLayout[]
     */
    public function getFieldLayoutsForSource(string $elementType, string $sourceKey): array
    {
        // Don't bother the element type for custom sources
        if (str_starts_with($sourceKey, 'custom:')) {
            return Craft::$app->getFields()->getLayoutsByType($elementType);
        }

        if (!isset($this->_fieldLayouts[$elementType][$sourceKey])) {
            /** @var string|ElementInterface $elementType */
            /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
            $this->_fieldLayouts[$elementType][$sourceKey] = $elementType::fieldLayouts($sourceKey);
        }
        return $this->_fieldLayouts[$elementType][$sourceKey];
    }

    /**
     * Returns additional sort options that should be available for a given element source.
     *
     * @param string $elementType The element type class
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $sourceKey The element source key
     * @return array[]
     */
    public function getSourceSortOptions(string $elementType, string $sourceKey): array
    {
        $event = new DefineSourceSortOptionsEvent([
            'elementType' => $elementType,
            'source' => $sourceKey,
        ]);

        $processedFieldIds = [];

        foreach ($this->getFieldLayoutsForSource($elementType, $sourceKey) as $fieldLayout) {
            foreach ($fieldLayout->getCustomFieldElements() as $layoutElement) {
                $field = $layoutElement->getField();
                if (
                    $field instanceof SortableFieldInterface &&
                    !isset($processedFieldIds[$field->id])
                ) {
                    $sortOption = $field->getSortOption();
                    if (!isset($sortOption['attribute'])) {
                        $sortOption['attribute'] = $sortOption['orderBy'];
                    }
                    $event->sortOptions[] = $sortOption;
                    $processedFieldIds[$field->id] = true;
                }
            }
        }

        $this->trigger(self::EVENT_DEFINE_SOURCE_SORT_OPTIONS, $event);
        return $event->sortOptions;
    }

    /**
     * Returns any table attributes that should be available for a given source, in addition to the [[getAvailableTableAttributes()|common attributes]].
     *
     * @param string $elementType The element type class
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $sourceKey The element source key
     * @return array[]
     */
    public function getSourceTableAttributes(string $elementType, string $sourceKey): array
    {
        $event = new DefineSourceTableAttributesEvent([
            'elementType' => $elementType,
            'source' => $sourceKey,
        ]);

        $processedFieldIds = [];
        $user = Craft::$app->getUser()->getIdentity();

        foreach ($this->getFieldLayoutsForSource($elementType, $sourceKey) as $fieldLayout) {
            foreach ($fieldLayout->getTabs() as $tab) {
                // Factor in the user condition for non-admins
                if ($user && !$user->admin && !($tab->getUserCondition()?->matchElement($user) ?? true)) {
                    continue;
                }

                foreach ($tab->getElements() as $layoutElement) {
                    if (!$layoutElement instanceof CustomField) {
                        continue;
                    }

                    $field = $layoutElement->getField();
                    if (
                        $field instanceof PreviewableFieldInterface &&
                        !isset($processedFieldIds[$field->id])
                    ) {
                        // Factor in the user condition for non-admins
                        if ($user && !$user->admin && !($layoutElement->getUserCondition()?->matchElement($user) ?? true)) {
                            continue;
                        }

                        $event->attributes["field:$field->uid"] = [
                            'label' => Craft::t('site', $field->name),
                        ];
                        $processedFieldIds[$field->id] = true;
                    }
                }
            }
        }

        $this->trigger(self::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES, $event);
        return $event->attributes;
    }

    /**
     * Returns the native sources for a given element type and context, normalized with `type` keys.
     *
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $context
     * @return array[]
     */
    private function _nativeSources(string $elementType, string $context): array
    {
        /** @var string|ElementInterface $elementType */
        /** @phpstan-var class-string<ElementInterface>|ElementInterface $elementType */
        $sources = $elementType::sources($context);
        $normalized = [];
        foreach ($sources as $source) {
            if (isset($source['type'])) {
                $normalized[] = $source;
            } elseif (array_key_exists('heading', $source)) {
                $source['type'] = self::TYPE_HEADING;
                $normalized[] = $source;
            } elseif (isset($source['key'])) {
                $source['type'] = self::TYPE_NATIVE;
                $normalized[] = $source;
            }
        }
        return $normalized;
    }

    /**
     * Returns the source configs for a given element type.
     *
     * @param string $elementType The element type class
     * @phpstan-param class-string<ElementInterface> $elementType
     * @return array[]|null
     */
    private function _sourceConfigs(string $elementType): ?array
    {
        return Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_ELEMENT_SOURCES . ".$elementType");
    }

    /**
     * Returns the source config for a given native source key.
     *
     * @param string $elementType
     * @phpstan-param class-string<ElementInterface> $elementType
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
