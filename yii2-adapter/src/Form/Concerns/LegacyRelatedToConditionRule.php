<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Concerns;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;

/** @phpstan-require-extends \CraftCms\Cms\Element\Conditions\RelatedToConditionRule */
trait LegacyRelatedToConditionRule
{
    use LegacyElementSelectConditionRule {
        inputHtml as private elementInputHtml;
        elementSelectConfig as private baseElementSelectConfig;
    }

    protected function inputHtml(): string
    {
        $id = 'element-type';

        return Html::hiddenLabel($this->getLabel(), $id) .
            Html::tag('div',
                FormFields::selectHtml([
                    'id' => $id,
                    'name' => 'elementType',
                    'options' => $this->_elementTypeOptions(),
                    'value' => $this->elementType,
                    'inputAttributes' => [
                        'hx' => [
                            'post' => Url::actionUrl('conditions/render'),
                        ],
                    ],
                ]) .
                $this->elementInputHtml(),
                [
                    'class' => ['flex', 'flex-start'],
                ]
            );
    }

    protected function elementSelectConfig(): array
    {
        return array_merge($this->baseElementSelectConfig(), [
            'showSiteMenu' => true,
        ]);
    }

    /** @return array<int, array{value: class-string<ElementInterface>, label: string}> */
    private function _elementTypeOptions(): array
    {
        return app(Fields::class)->getRelationalFieldTypes()->map(function(string $field) {
            /** @var class-string<BaseRelationField> $field */
            $elementType = $field::elementType();

            return [
                'value' => $elementType,
                'label' => $elementType::displayName(),
            ];
        })->all();
    }
}
