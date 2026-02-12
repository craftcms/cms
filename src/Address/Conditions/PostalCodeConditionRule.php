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

final class PostalCodeConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Postal Code');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['postalCode'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        $query->postalCode($this->paramValue());
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->postalCode);
    }
}
