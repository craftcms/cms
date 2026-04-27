<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Facades\Addresses;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use Override;

use function CraftCms\Cms\t;

class AdministrativeAreaConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public string $countryCode = 'US';

    #[Override]
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'countryCode' => $this->countryCode,
        ]);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'countryCode' => ['required', 'string'],
        ]);
    }

    public function getLabel(): string
    {
        return t('Administrative Area');
    }

    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    protected function options(): array
    {
        $administrativeAreas = Addresses::getSubdivisionRepository()->getList([$this->countryCode], app()->getLocale());
        // Allow custom states that are currently in the administrative areas list to remain in the list.
        foreach ($this->getValues() as $val) {
            if (! in_array($val, $administrativeAreas)) {
                $administrativeAreas[$val] = $val;
            }
        }

        return $administrativeAreas;
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        $query->administrativeArea($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->administrativeArea);
    }

    #[Override]
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
            Html::hiddenLabel(Html::encode($this->getLabel()), $multiSelectId).
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

        return $countrySelect.$adminSelectize;
    }
}
