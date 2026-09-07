<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class AddressLine1ConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Address Line 1');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        AddressQuery::applyAddressLine1($query, $this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->addressLine1);
    }
}
