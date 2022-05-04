<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\exporters;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementExporter;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;

/**
 * Expanded represents an "Expanded" element exporter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class Expanded extends ElementExporter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Expanded');
    }

    /**
     * @inheritdoc
     */
    public function export(ElementQueryInterface $query): mixed
    {
        // Eager-load as much as we can
        $eagerLoadableFields = [];
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof EagerLoadingFieldInterface) {
                $eagerLoadableFields[] = $field->handle;
            }
        }

        $data = [];

        /** @var ElementQuery $query */
        $query->with($eagerLoadableFields);

        foreach (Db::each($query) as $element) {
            /** @var ElementInterface $element */
            // Get the basic array representation excluding custom fields
            $attributes = array_flip($element->attributes());
            if (($fieldLayout = $element->getFieldLayout()) !== null) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    unset($attributes[$field->handle]);
                }
            }
            $elementArr = $element->toArray(array_keys($attributes));
            if ($fieldLayout !== null) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $value = $element->getFieldValue($field->handle);
                    $elementArr[$field->handle] = $field->serializeValue($value, $element);
                }
            }
            $data[] = $elementArr;
        }

        return $data;
    }
}
