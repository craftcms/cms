<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Condition\BaseTextConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

class UsernameConditionRule extends BaseTextConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Username');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        UserQuery::applyUsername($query, $this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->username);
    }
}
