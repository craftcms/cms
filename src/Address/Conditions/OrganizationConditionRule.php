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

final class OrganizationConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Organization');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['organization'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var AddressQuery $query */
        $query->organization($this->paramValue());
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var Address $element */
        return $this->matchValue($element->organization);
    }
}
