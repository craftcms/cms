<?php

namespace craft\conditions\elements\users;

use Craft;
use craft\conditions\BaseSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * User group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class GroupConditionRule extends BaseSelectConditionRule implements QueryConditionRuleInterface
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
    protected function options(): array
    {
        $groups = Craft::$app->getUserGroups()->getAllGroups();
        return ArrayHelper::map($groups, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $group = Craft::$app->getUserGroups()->getGroupByUid($this->value);

        if ($group) {
            /** @var UserQuery $query */
            $query->group($group);
        }
    }
}
