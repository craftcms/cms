<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Exporters;

use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\helpers\Component;
use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Field\Contracts\EagerLoadingFieldInterface;
use CraftCms\Cms\Field\Fields;

use function CraftCms\Cms\t;

class Expanded extends ElementExporter
{
    #[\Override]
    public static function displayName(): string
    {
        return t('Expanded');
    }

    public function export(ElementQueryInterface $query): mixed
    {
        $eagerLoadableFields = [];

        foreach (app(Fields::class)->getAllFields() as $field) {
            if (! $field instanceof EagerLoadingFieldInterface) {
                continue;
            }

            $eagerLoadableFields[] = [
                'path' => $field->handle,
                'criteria' => [
                    'status' => null,
                ],
            ];
        }

        $data = [];

        /** @var ElementQuery $query */
        $query->with($eagerLoadableFields);

        $query->each(function (ElementInterface $element) use (&$data) {
            /** @var ElementInterface $element */
            $attributes = array_flip($element->attributes());

            if (($fieldLayout = $element->getFieldLayout()) !== null) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    unset($attributes[$field->handle]);
                }
            }

            $datetimeAttributes = Component::datetimeAttributes($element);
            $otherAttributes = array_diff(array_keys($attributes), $datetimeAttributes);
            $elementArr = $element->toArray($otherAttributes);

            foreach ($datetimeAttributes as $attribute) {
                $date = $element->$attribute;
                $elementArr[$attribute] = $date ? DateTimeHelper::toIso8601($date) : $element->$attribute;
            }

            uksort($elementArr, fn ($a, $b) => $attributes[$a] <=> $attributes[$b]);

            if ($fieldLayout !== null) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    $value = $element->getFieldValue($field->handle);
                    $elementArr[$field->handle] = $field->serializeValue($value, $element);
                }
            }

            $data[] = $elementArr;
        }, 100);

        return $data;
    }
}
