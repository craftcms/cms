<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseLightswitchConditionRule;
use craft\conditions\ConditionInterface;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\conditions\elements\entry\EntryQueryCondition;
use craft\elements\db\ElementQuery;
use yii\db\QueryInterface;

/**
 * Element trashed condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class HasUrlConditionRule extends BaseLightswitchConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Has URL');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQuery $query */
        if ($this->value) {
            $query->uri('not :empty:');
        } else {
            $query->uri(':empty:');
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
