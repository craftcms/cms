<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Combobox;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Support\Facades\Addresses;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

class AdministrativeAreaConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof AddressCondition) {
            return false;
        }

        return true;
    }

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

    /** @return list<Node> */
    #[Override]
    protected function inputNodes(): array
    {
        return [
            Field::make(t('Country'), Choice::make('countryCode')
                ->options($this->formOptions(Addresses::getCountryList()))
                ->withoutPlaceholder()
                ->value($this->countryCode)
                ->reactive()),
            Field::make($this->getLabel(), Combobox::make('values')
                ->multiple()
                ->options($this->formOptions($this->options()))
                ->showAllOnEmpty()
                ->value($this->getValues())),
        ];
    }
}
