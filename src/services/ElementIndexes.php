<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\db\Query;
use craft\db\Table;
use craft\events\DefineSourceSortOptionsEvent;
use craft\events\DefineSourceTableAttributesEvent;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\models\FieldLayout;
use yii\base\Component;

/**
 * The ElementIndexes service provides APIs for managing element indexes.
 * An instance of ElementIndexes service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getElementIndexes()|`Craft::$app->elementIndexes`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ElementIndexes extends Component
{
    /**
     * @event DefineSourceTableAttributesEvent The event that is triggered when defining the available table attributes for a source.
     * @since 3.6.5
     */
    const EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES = 'defineSourceTableAttributes';

    /**
     * @event DefineSourceSortOptionsEvent The event that is triggered when defining the available sort options for a source.
     * @since 3.6.5
     */
    const EVENT_DEFINE_SOURCE_SORT_OPTIONS = 'defineSourceSortOptions';

    private $_indexSettings;

    /**
     * Returns the element index settings for a given element type.
     *
     * @param string $elementType The element type class
     * @return array|null
     */
    public function getSettings(string $elementType)
    {
        if ($this->_indexSettings === null || !array_key_exists($elementType, $this->_indexSettings)) {
            $result = (new Query())
                ->select(['settings'])
                ->from([Table::ELEMENTINDEXSETTINGS])
                ->where(['type' => $elementType])
                ->scalar();

            if ($result) {
                $this->_indexSettings[$elementType] = Json::decode($result);
            } else {
                $this->_indexSettings[$elementType] = null;
            }
        }

        return $this->_indexSettings[$elementType];
    }

    /**
     * Saves new element index settings for a given element type.
     *
     * @param string $elementType The element type class
     * @param array $newSettings The new index settings
     * @return bool Whether the settings were saved successfully
     */
    public function saveSettings(string $elementType, array $newSettings): bool
    {
        /** @var string|ElementInterface $elementType */
        // Get the currently saved settings
        $settings = $this->getSettings($elementType);
        $baseSources = $this->_normalizeSources($elementType::sources('index'));

        // Updating the source order?
        if (isset($newSettings['sourceOrder'])) {
            // Only actually save a custom order if it's different from the default order
            $saveSourceOrder = false;

            if (count($newSettings['sourceOrder']) !== count($baseSources)) {
                $saveSourceOrder = true;
            } else {
                foreach ($baseSources as $i => $source) {
                    // Any differences?
                    if (
                        (array_key_exists('heading', $source) && (
                                $newSettings['sourceOrder'][$i][0] !== 'heading' ||
                                $newSettings['sourceOrder'][$i][1] !== $source['heading']
                            )) ||
                        (array_key_exists('key', $source) && (
                                $newSettings['sourceOrder'][$i][0] !== 'key' ||
                                $newSettings['sourceOrder'][$i][1] !== $source['key']
                            ))
                    ) {
                        $saveSourceOrder = true;
                        break;
                    }
                }
            }

            if ($saveSourceOrder) {
                $settings['sourceOrder'] = $newSettings['sourceOrder'];
            } else {
                unset($settings['sourceOrder']);
            }
        }

        // Updating the source settings?
        if (isset($newSettings['sources'])) {
            // Merge in the new source settings
            if (!isset($settings['sources'])) {
                $settings['sources'] = $newSettings['sources'];
            } else {
                $settings['sources'] = array_merge($settings['sources'], $newSettings['sources']);
            }

            // Prune out any settings for sources that don't exist
            $indexedBaseSources = $this->_indexSourcesByKey($baseSources);

            foreach ($settings['sources'] as $key => &$source) {
                if (!isset($indexedBaseSources[$key])) {
                    unset($settings['sources'][$key]);
                } else if (empty($source['headerColHeading'])) {
                    unset($source['headerColHeading']);
                }
            }
            unset($source);
        }

        $success = (bool)Db::upsert(Table::ELEMENTINDEXSETTINGS, [
            'type' => $elementType,
        ], [
            'settings' => Json::encode($settings),
        ]);

        if (!$success) {
            return false;
        }

        $this->_indexSettings[$elementType] = $settings;
        return true;
    }

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param string $elementType The element type class
     * @param string $context The context
     * @return array
     */
    public function getSources(string $elementType, string $context = 'index'): array
    {
        /** @var string|ElementInterface $elementType */
        $settings = $this->getSettings($elementType);
        $baseSources = $this->_normalizeSources($elementType::sources($context));
        $sources = [];

        // Should we output the sources in a custom order?
        if (isset($settings['sourceOrder'])) {
            // Index the sources by their keys
            $indexedBaseSources = $this->_indexSourcesByKey($baseSources);

            // Assemble the customized source list
            $pendingHeading = null;

            foreach ($settings['sourceOrder'] as [$type, $value]) {
                if ($type === 'heading') {
                    // Queue it up. We'll only add it if a real source follows
                    $pendingHeading = $value;
                } else if (isset($indexedBaseSources[$value])) {
                    // If there's a pending heading, add that first
                    if ($pendingHeading !== null) {
                        $sources[] = ['heading' => $pendingHeading];
                        $pendingHeading = null;
                    }

                    $sources[] = $indexedBaseSources[$value];

                    // Unset this so we can have a record of unused sources afterward
                    unset($indexedBaseSources[$value]);
                }
            }

            // Append any remaining sources to the end of the list
            if (!empty($indexedBaseSources)) {
                $sources[] = ['heading' => ''];

                foreach ($indexedBaseSources as $source) {
                    $sources[] = $source;
                }
            }
        } else {
            $sources = $baseSources;
        }

        return $sources;
    }

    /**
     * Returns all of the available attributes that can be shown for a given element type source.
     *
     * @param string $elementType The element type class
     * @return array
     */
    public function getAvailableTableAttributes(string $elementType): array
    {
        /** @var string|ElementInterface $elementType */
        $attributes = $elementType::tableAttributes();

        // Normalize
        foreach ($attributes as $key => $info) {
            if (!is_array($info)) {
                $attributes[$key] = ['label' => $info];
            } else if (!isset($info['label'])) {
                $attributes[$key]['label'] = '';
            }
        }

        return $attributes;
    }

    /**
     * Returns the attributes that should be shown for a given element type source.
     *
     * @param string $elementType The element type class
     * @param string $sourceKey The element type source key
     * @return array
     */
    public function getTableAttributes(string $elementType, string $sourceKey): array
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

        // Get the source settings
        $settings = $this->getSettings($elementType);

        $attributes = [];

        // Start with the first available attribute, no matter what
        $firstKey = null;
        foreach ($availableAttributes as $key => $attributeInfo) {
            $firstKey = $key;
            if (isset($settings['sources'][$sourceKey]['headerColHeading'])) {
                $attributeInfo['defaultLabel'] = $attributeInfo['label'];
                $attributeInfo['label'] = $settings['sources'][$sourceKey]['headerColHeading'];
            }
            $attributes[] = [$key, $attributeInfo];
            break;
        }

        // Is there a custom attributes list?
        if (isset($settings['sources'][$sourceKey]['tableAttributes'])) {
            $attributeKeys = $settings['sources'][$sourceKey]['tableAttributes'];
        } else {
            $attributeKeys = $elementType::defaultTableAttributes($sourceKey);
        }

        // Assemble the remainder of the list
        foreach ($attributeKeys as $key) {
            if ($key != $firstKey && isset($availableAttributes[$key])) {
                $attributes[] = [$key, $availableAttributes[$key]];
            }
        }

        return $attributes;
    }

    /**
     * @var array
     * @see getFieldLayoutsForSource()
     */
    private $_fieldLayouts;

    /**
     * Returns all the field layouts available for the given element source.
     *
     * @param string $elementType
     * @param string $sourceKey
     * @return FieldLayout[]
     * @since 3.5.0
     */
    public function getFieldLayoutsForSource(string $elementType, string $sourceKey): array
    {
        if (!isset($this->_fieldLayouts[$elementType][$sourceKey])) {
            /** @var string|ElementInterface $elementType */
            $this->_fieldLayouts[$elementType][$sourceKey] = $elementType::fieldLayouts($sourceKey);
        }
        return $this->_fieldLayouts[$elementType][$sourceKey];
    }

    /**
     * Returns additional sort options that should be available for a given element source.
     *
     * @param string $elementType The element type class
     * @param string $sourceKey The element source key
     * @return array
     * @since 3.5.0
     */
    public function getSourceSortOptions(string $elementType, string $sourceKey): array
    {
        $event = new DefineSourceSortOptionsEvent([
            'elementType' => $elementType,
            'source' => $sourceKey,
        ]);

        $processedFieldIds = [];

        foreach ($this->getFieldLayoutsForSource($elementType, $sourceKey) as $fieldLayout) {
            foreach ($fieldLayout->getFields() as $field) {
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
     * Returns additional table attributes that should be available for a given source.
     *
     * @param string $elementType The element type class
     * @param string $sourceKey The element source key
     * @return array
     * @since 3.5.0
     */
    public function getSourceTableAttributes(string $elementType, string $sourceKey): array
    {
        $event = new DefineSourceTableAttributesEvent([
            'elementType' => $elementType,
            'source' => $sourceKey,
        ]);

        $processedFieldIds = [];

        foreach ($this->getFieldLayoutsForSource($elementType, $sourceKey) as $fieldLayout) {
            foreach ($fieldLayout->getFields() as $field) {
                if (
                    $field instanceof PreviewableFieldInterface &&
                    !isset($processedFieldIds[$field->id])
                ) {
                    $event->attributes["field:$field->id"] = [
                        'label' => Craft::t('site', $field->name),
                    ];
                    $processedFieldIds[$field->id] = true;
                }
            }
        }

        $this->trigger(self::EVENT_DEFINE_SOURCE_TABLE_ATTRIBUTES, $event);
        return $event->attributes;
    }

    /**
     * Returns the fields that are available to be shown as table attributes.
     *
     * @param string $elementType The element type class
     * @return FieldInterface[]
     * @deprecated in 3.5.0. Use [[getSourceTableAttributes()]] instead.
     */
    public function getAvailableTableFields(string $elementType): array
    {
        /** @var string|ElementInterface $elementType */
        $fields = Craft::$app->getFields()->getFieldsByElementType($elementType);
        $availableFields = [];

        foreach ($fields as $field) {
            if ($field instanceof PreviewableFieldInterface) {
                $availableFields[] = $field;
            }
        }

        return $availableFields;
    }

    /**
     * Normalizes an element type’s source list.
     *
     * @param array $sources
     * @return array
     */
    private function _normalizeSources(array $sources): array
    {
        if (!is_array($sources)) {
            return [];
        }

        $normalizedSources = [];
        $pendingHeading = null;

        foreach ($sources as $source) {
            // Is this a heading?
            if (array_key_exists('heading', $source)) {
                $pendingHeading = $source['heading'];
            } else {
                // Is there a pending heading?
                if ($pendingHeading !== null) {
                    $normalizedSources[] = ['heading' => $pendingHeading];
                    $pendingHeading = null;
                }

                // Only allow sources that have a key
                if (empty($source['key'])) {
                    continue;
                }

                $normalizedSources[] = $source;
            }
        }

        return $normalizedSources;
    }

    /**
     * Indexes a list of sources by their key.
     *
     * @param array $sources
     * @return array
     */
    private function _indexSourcesByKey(array $sources): array
    {
        $indexedSources = [];

        foreach ($sources as $source) {
            if (isset($source['key'])) {
                $indexedSources[$source['key']] = $source;
            }
        }

        return $indexedSources;
    }
}
