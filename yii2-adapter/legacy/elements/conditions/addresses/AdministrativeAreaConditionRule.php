<?php

declare(strict_types=1);

namespace craft\elements\conditions\addresses;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Facades\Addresses;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use CraftCms\Yii2Adapter\Form\Concerns\LegacyMultiSelectConditionRule;

/** @deprecated 6.0.0 Use \CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule instead. */
class AdministrativeAreaConditionRule extends \CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule
{
    use LegacyMultiSelectConditionRule;

    protected function inputHtml(): string
    {
        $countrySelect = FormFields::selectFieldHtml([
            'id' => 'country-code',
            'name' => 'countryCode',
            'options' => Addresses::getCountryList(),
            'value' => $this->countryCode,
            'inputAttributes' => [
                'hx' => [
                    'post' => Url::actionUrl('conditions/render'),
                ],
            ],
        ]);

        $multiSelectId = 'multiselect';

        $adminSelectize =
            Html::hiddenLabel(Html::encode($this->getLabel()), $multiSelectId) .
            FormFields::selectizeHtml([
                'id' => $multiSelectId,
                'class' => 'selectize fullwidth',
                'name' => 'values',
                'values' => $this->getValues(),
                'options' => $this->options(),
                'multi' => true,
                'selectizeOptions' => [
                    'create' => true, // Must allow creation since administrative area field on addresses could be free text input
                ],
            ]);

        return $countrySelect . $adminSelectize;
    }
}
