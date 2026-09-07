<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Condition\BaseLightswitchConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;
use Override;

use function CraftCms\Cms\t;

class AdminConditionRule extends BaseLightswitchConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof UserCondition) {
            return false;
        }

        // Exclude from the Admins source
        if ($condition->sourceKey === 'admins') {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('Admin');
    }

    #[Override]
    public static function isSelectable(): bool
    {
        return Edition::isAtLeast(Edition::Pro);
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        UserQuery::applyAdmin($query, $this->value);
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue($element->admin);
    }
}
