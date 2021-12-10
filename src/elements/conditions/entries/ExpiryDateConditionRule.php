<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;

/**
 * Element expiry date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ExpiryDateConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Expiry Date');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['expiryDate'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->expiryDate($this->queryParamValue());
    }
}
