<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseSelectOperatorConditionRule;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;
use yii\db\QueryInterface;

/**
 * Element status condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class StatusConditionRule extends BaseSelectOperatorConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Status');
    }

    /**
     * @inheritdoc
     */
    public function getSelectOptions(): array
    {
        return Entry::statuses();
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQuery $query */
        $query->status($this->optionValue);
    }
}
