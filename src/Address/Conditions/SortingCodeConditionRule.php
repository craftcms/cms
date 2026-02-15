<?php

declare(strict_types=1);

namespace CraftCms\Cms\Address\Conditions;

use craft\base\ElementInterface;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

use function CraftCms\Cms\t;

final class SortingCodeConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Sorting Code');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['sortingCode'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        $query->sortingCode($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->sortingCode);
    }
}
