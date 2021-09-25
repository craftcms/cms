<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseLightswitchConditionRule;
use craft\conditions\ConditionInterface;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQuery;
use yii\db\QueryInterface;

/**
 * Element has URL condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class HasUrlConditionRule extends BaseLightswitchConditionRule implements QueryConditionRuleInterface
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
    public static function exclusiveQueryParams(): array
    {
        return ['uri'];
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
}
