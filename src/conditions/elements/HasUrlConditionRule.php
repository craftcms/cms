<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseLightswitchConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
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
    public function getLabel(): string
    {
        return Craft::t('app', 'Has URL');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['uri'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        if ($this->value) {
            $query->uri('not :empty:');
        } else {
            $query->uri(':empty:');
        }
    }
}
