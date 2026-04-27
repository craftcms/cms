<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

class FullNameConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Full Name');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['fullName'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        $query->fullName($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->fullName);
    }
}
