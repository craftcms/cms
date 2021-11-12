<?php

namespace craft\conditions\elements\entries;

use Craft;
use craft\conditions\BaseMultiSelectConditionRule;
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
class AuthorGroupConditionRule extends BaseMultiSelectConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Author Group');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['authorGroup', 'authorGroupId'];
    }

    /**
     * @inheritdoc
     */
    protected function options(): array
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
        }, $this->getValues()));

        $query->authorGroup($userGroups);
    }
}
