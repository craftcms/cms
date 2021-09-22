<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseDateRangeConditionRule;
use craft\conditions\ConditionInterface;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\QueryInterface;

/**
 * Element expiry date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ExpiryDateConditionRule extends BaseDateRangeConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Expiry Date');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQuery $query */
        if ($this->startDate) {
            $query->subQuery->andWhere(Db::parseDateParam('entries.expiryDate', $this->startDate, '>='));
        }

        if ($this->endDate) {
            $query->subQuery->andWhere(Db::parseDateParam('entries.expiryDate', $this->endDate, '<'));
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
