<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use craft\base\ElementInterface;
use craft\elements\db\UserQuery;
use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\User\Elements\User;

use function CraftCms\Cms\t;

class UsernameConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return t('Username');
    }

    /**
     * {@inheritdoc}
     */
    public function getExclusiveQueryParams(): array
    {
        return ['username'];
    }

    /**
     * {@inheritdoc}
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->username($this->paramValue());
    }

    /**
     * {@inheritdoc}
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->username);
    }
}
