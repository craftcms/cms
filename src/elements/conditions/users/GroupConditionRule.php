<?php

namespace craft\elements\conditions\users;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\helpers\ArrayHelper;

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
        return Craft::t('app', 'User Group');
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
        return !empty(Craft::$app->getUserGroups()->getAllGroups());
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        $groups = Craft::$app->getUserGroups()->getAllGroups();
        return ArrayHelper::map($groups, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var UserQuery $query */
        $userGroups = Craft::$app->getUserGroups();
        $query->groupId($this->paramValue(fn($uid) => $userGroups->getGroupByUid($uid)->id ?? null));
    }


    /**
     * @param \craft\base\ElementInterface $element
     *
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var User $element */
        $groups = $element->getGroups();
        foreach ($groups as $group) {
            if ($this->matchValue($group->uid)) {
                return true;
            }
        }
        return false;
    }
}
