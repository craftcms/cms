<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Support\Facades\Addresses;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Url;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

class AdministrativeAreaConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public string $countryCode = 'US';

    /** @return array<string, mixed> */
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

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        AddressQuery::applyAdministrativeArea($query, $this->paramValue());
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
