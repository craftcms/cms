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
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
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
                $eagerLoadableFields[] = [
                    'path' => $field->handle,
                    'criteria' => [
                        'status' => null,
                    ],
                ];
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
            // because of changes in https://github.com/craftcms/cms/commit/e662ee32d7a5c15dfaa911ae462155615ce7a320
            // we need to split attributes to the date ones and all other;
            // pass all other to toArray()
            // and then return DateTimeHelper::toIso8601($date, false); for all the date ones
            $datetimeAttributes = Component::datetimeAttributes($element);
            $otherAttributes = array_diff(array_keys($attributes), $datetimeAttributes);

            $elementArr = $element->toArray($otherAttributes);

            foreach ($datetimeAttributes as $attribute) {
                $date = $element->$attribute;
                if ($date) {
                    $elementArr[$attribute] = DateTimeHelper::toIso8601($date);
                } else {
                    $elementArr[$attribute] = $element->$attribute;
                }
            }

            // sort the $elementArr so the keys are in the same order as the values in the $attributes table
            uksort($elementArr, fn($a, $b) => $attributes[$a] <=> $attributes[$b]);

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
