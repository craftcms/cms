<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Conditions;

use craft\base\ElementInterface;
use craft\elements\db\UserQuery;
use CraftCms\Cms\Condition\BaseMultiSelectConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

class GroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('User Group');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['group', 'groupId'];
    }

    #[\Override]
    public static function isSelectable(): bool
    {
        return UserGroups::getAllGroups()->isNotEmpty();
    }

    protected function options(): array
    {
        return UserGroups::getAllGroups()->pluck('name', 'uid')->all();
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->groupId($this->paramValue(fn ($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    /**
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue(Arr::pluck($element->getGroups(), 'uid'));
    }
}
