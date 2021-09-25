<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseDateRangeConditionRule;
use craft\conditions\ConditionInterface;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\EntryQuery;
use yii\db\QueryInterface;

/**
 * Element expiry date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ExpiryDateConditionRule extends BaseDateRangeConditionRule implements QueryConditionRuleInterface
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
    public static function exclusiveQueryParams(): array
    {
        return ['expiryDate'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $startDate = $this->getStartDate();
        $endDate = $this->getEndDate();

        if ($startDate || $endDate) {
            $query->expiryDate(array_filter([
                'and',
                $startDate ? ">= $startDate" : null,
                $endDate ? "< $endDate" : null,
            ]));
        }
    }
}
