<?php

namespace craft\elements\conditions\users;

use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\UserQuery;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User;
use yii\base\InvalidConfigException;
use function CraftCms\Cms\t;

/**
 * User group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class GroupConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return t('User Group');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['group', 'groupId'];
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return UserGroups::getAllGroups()->isNotEmpty();
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return UserGroups::getAllGroups()->pluck('name', 'uid')->all();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->groupId($this->paramValue(fn($uid) => UserGroups::getGroupByUid($uid)->id ?? null));
    }

    /**
     * @param ElementInterface $element
     * @return bool
     * @throws InvalidConfigException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        return $this->matchValue(Arr::pluck($element->getGroups(), 'uid'));
    }
}
