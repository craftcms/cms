<?php

namespace craft\conditions\elements\category;

use Craft;
use craft\conditions\BaseSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\CategoryQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Category group condition rule.
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
        return Craft::t('app', 'Category Group');
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
        $groups = Craft::$app->getCategories()->getAllGroups();
        return ArrayHelper::map($groups, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $group = Craft::$app->getCategories()->getGroupByUid($this->value);

        if ($group) {
            /** @var CategoryQuery $query */
            $query->group($group);
        }
    }
}
