<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseMultiSelectOperatorConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Author group condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AuthorGroupConditionRule extends BaseMultiSelectOperatorConditionRule implements QueryConditionRuleInterface
{
    protected string $_id = 'author-groups';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Author Group');
    }

    /**
     * @inheritdoc
     */
    public static function queryParams(): array
    {
        return ['authorGroup', 'authorGroupId'];
    }

    /**
     * @inheritdoc
     */
    public function getSelectOptions(): array
    {
        $sections = Craft::$app->getUserGroups()->getAllGroups();
        return ArrayHelper::map($sections, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $userGroupsService = Craft::$app->getUserGroups();
        $userGroups = array_filter(array_map(static function(string $uid) use ($userGroupsService) {
            return $userGroupsService->getGroupByUid($uid);
        }, $this->_optionValues));

        $query->authorGroup($userGroups);
    }
}
