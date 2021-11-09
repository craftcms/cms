<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseDateRangeConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use yii\db\QueryInterface;

/**
 * Element post date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class PostDateConditionRule extends BaseDateRangeConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Post Date');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['postDate', 'after', 'before'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->postDate($this->paramValue());
    }
}
