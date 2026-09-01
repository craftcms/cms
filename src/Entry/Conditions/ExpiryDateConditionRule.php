<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;

use function CraftCms\Cms\t;

class ExpiryDateConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Expiry Date');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['expiryDate'];
    }

    /** @param EntryQuery<Entry> $query */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->expiryDate($this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue($element->expiryDate);
    }
}
