<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\base\PreviewableFieldInterface;
use craft\app\db\Query;
use craft\app\helpers\Json;
use yii\base\Component;

/**
 * The ElementIndexes service provides APIs for managing element indexes.
 *
 * An instance of ElementIndexes service is globally accessible in Craft via [[Application::elements `Craft::$app->getElements()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementIndexes extends Component
{
    // Properties
    // =========================================================================

    private $_indexSettings;

    // Public Methods
    // =========================================================================

    /**
     * Returns the element index settings for a given element type.
     *
     * @param ElementInterface|string $elementType The element type class
     *
     * @return array|null
     */
    public function getSettings($elementType)
    {
        if ($this->_indexSettings === null || !array_key_exists($elementType, $this->_indexSettings)) {
            $result = (new Query())
                ->select('settings')
                ->from('{{%elementindexsettings}}')
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
     * @param ElementInterface|string $elementType The element type class
     * @param array                   $newSettings The new index settings
     *
     * @return boolean Whether the settings were saved successfully
     */
    public function saveSettings($elementType, $newSettings)
    {
        // Get the currently saved settings
        $settings = $this->getSettings($elementType);
        $baseSources = $this->_normalizeSources($elementType::getSources('index'));

        // Updating the source order?
        if (isset($newSettings['sourceOrder'])) {
            // Only actually save a custom order if it's different from the default order
            $saveSourceOrder = false;

            if (count($newSettings['sourceOrder']) != count($baseSources)) {
                $saveSourceOrder = true;
            } else {
                foreach ($baseSources as $i => $source) {
                    // Any differences?
                    if (
                        (array_key_exists('heading', $source) && (
                                $newSettings['sourceOrder'][$i][0] != 'heading' ||
                                $newSettings['sourceOrder'][$i][1] != $source['heading']
                            )) ||
                        (array_key_exists('key', $source) && (
                                $newSettings['sourceOrder'][$i][0] != 'key' ||
                                $newSettings['sourceOrder'][$i][1] != $source['key']
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

            foreach ($settings['sources'] as $key => $source) {
                if (!isset($indexedBaseSources[$key])) {
                    unset($settings['sources']);
                }
            }
        }

        $affectedRows = Craft::$app->getDb()->createCommand()
            ->insertOrUpdate(
                '{{%elementindexsettings}}',
                ['type' => $elementType],
                ['settings' => Json::encode($settings)])
            ->execute();

        if ($affectedRows) {
            $this->_indexSettings[$elementType] = $settings;

            return true;
        }

        return false;
    }

    /**
     * Returns the element index sources in the custom groupings/order.
     *
     * @param ElementInterface|string $elementType The element type class
     * @param string                  $context     The context
     *
     * @return array
     */
    public function getSources($elementType, $context = 'index')
    {
        $settings = $this->getSettings($elementType);
        $baseSources = $this->_normalizeSources($elementType::getSources($context));
        $sources = [];

        // Should we output the sources in a custom order?
        if (isset($settings['sourceOrder'])) {
            // Index the sources by their keys
            $indexedBaseSources = $this->_indexSourcesByKey($baseSources);

            // Assemble the customized source list
            $pendingHeading = null;

            foreach ($settings['sourceOrder'] as $source) {
                list($type, $value) = $source;

                if ($type == 'heading') {
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
            if ($indexedBaseSources) {
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
     * @param ElementInterface|string $elementType   The element type class
     * @param boolean                 $includeFields Whether custom fields should be included in the list
     *
     * @return array
     */
    public function getAvailableTableAttributes($elementType, $includeFields = true)
    {
        $attributes = $elementType::defineAvailableTableAttributes();

        foreach ($attributes as $key => $info) {
            if (!is_array($info)) {
                $attributes[$key] = ['label' => $info];
            } else if (!isset($info['label'])) {
                $attributes[$key]['label'] = '';
            }
        }

        if ($includeFields) {
            // Mix in custom fields
            foreach ($this->getAvailableTableFields($elementType) as $field) {
                /** @var Field $field */
                $attributes['field:'.$field->id] = ['label' => Craft::t($field->name, 'site')];
            }
        }

        return $attributes;
    }

    /**
     * Returns the attributes that should be shown for a given element type source.
     *
     * @param ElementInterface|string $elementType The element type class
     * @param string                  $sourceKey   The element type source key
     *
     * @return array
     */
    public function getTableAttributes($elementType, $sourceKey)
    {
        $settings = $this->getSettings($elementType);
        $availableAttributes = $this->getAvailableTableAttributes($elementType);
        $attributes = [];

        // Start with the first available attribute, no matter what
        $firstKey = null;

        foreach ($availableAttributes as $key => $attributeInfo) {
            $firstKey = $key;
            $attributes[] = [$key, $attributeInfo];
            break;
        }

        // Is there a custom attributes list?
        if (isset($settings['sources'][$sourceKey]['tableAttributes'])) {
            $attributeKeys = $settings['sources'][$sourceKey]['tableAttributes'];
        } else {
            $attributeKeys = $elementType::getDefaultTableAttributes($sourceKey);
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
     * Returns the fields that are available to be shown as table attributes.
     *
     * @param ElementInterface|string $elementType The element type class
     *
     * @return FieldInterface[]
     */
    public function getAvailableTableFields($elementType)
    {
        $fields = Craft::$app->getFields()->getFieldsByElementType($elementType);
        $availableFields = [];

        foreach ($fields as $field) {
            $fieldType = $field->getType();

            if ($fieldType && $fieldType instanceof PreviewableFieldInterface) {
                $availableFields[] = $field;
            }
        }

        return $availableFields;
    }

    // Private Methods
    // =========================================================================

    /**
     * Normalizes an element type’s source list.
     *
     * @param array $sources
     *
     * @return array
     */
    private function _normalizeSources($sources)
    {
        if (!is_array($sources)) {
            return [];
        }

        $normalizedSources = [];
        $pendingHeading = null;

        foreach ($sources as $key => $source) {
            // Is this a heading?
            if (array_key_exists('heading', $source)) {
                $pendingHeading = $source['heading'];
            } else {
                // Is there a pending heading?
                if ($pendingHeading !== null) {
                    $normalizedSources[] = ['heading' => $pendingHeading];
                    $pendingHeading = null;
                }

                // Ensure the key is specified in the source
                if (!is_numeric($key)) {
                    $source['key'] = $key;
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
     *
     * @return array
     */
    private function _indexSourcesByKey($sources)
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
