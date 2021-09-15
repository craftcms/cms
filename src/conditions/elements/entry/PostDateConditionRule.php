<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseDateRangeConditionRule;
use craft\conditions\ConditionInterface;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\conditions\elements\entry\EntryQueryCondition;
use craft\elements\db\ElementQuery;
use yii\db\QueryInterface;

/**
 * Element post date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class PostDateConditionRule extends BaseDateRangeConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Post Date');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQuery $query */
        if ($this->startDate) {
            $query->after($this->startDate);
        }

        if ($this->endDate) {
            $query->before($this->endDate);
        }
    }

    /**
     * @inheritdoc
     */
    public function validateCondition(ConditionInterface $condition): bool
    {
        return parent::validateCondition($condition) && $condition instanceof EntryQueryCondition;
    }
}
