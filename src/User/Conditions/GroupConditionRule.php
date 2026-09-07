<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Contracts\Database\Query\Builder;
use Override;
use RuntimeException;

use function CraftCms\Cms\t;

class GroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public static function isSelectableForCondition(ConditionInterface $condition): bool
    {
        if (! $condition instanceof UserCondition) {
            return false;
        }

        // Exclude from user group sources
        if (isset($condition->sourceKey) && str_starts_with($condition->sourceKey, 'group:')) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return t('User Group');
    }

    #[Override]
    public static function isSelectable(): bool
    {
        return UserGroups::getAllGroups()->isNotEmpty();
    }

    protected function options(): array
    {
        return UserGroups::getAllGroups()->pluck('name', 'uid')->all();
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        UserQuery::applyGroupId($query, $this->paramValue(fn ($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    /**
     * @throws RuntimeException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue(Arr::pluck($element->getGroups(), 'uid'));
    }
}
