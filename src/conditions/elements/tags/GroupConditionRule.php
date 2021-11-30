<?php

namespace craft\conditions\elements\tags;

use Craft;
use craft\conditions\BaseSelectConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\TagQuery;
use craft\helpers\ArrayHelper;
use yii\db\QueryInterface;

/**
 * Tag group condition rule.
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
        return Craft::t('app', 'Tag Group');
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
        $groups = Craft::$app->getTags()->getAllTagGroups();
        return ArrayHelper::map($groups, 'uid', 'name');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        $group = Craft::$app->getTags()->getTagGroupByUid($this->value);

        if ($group) {
            /** @var TagQuery $query */
            $query->group($group);
        }
    }
}
