<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseLightswitchConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

/**
 * Element trashed condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class TrashedConditionRule extends BaseLightswitchConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Trashed');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['trashed'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->trashed($this->value);
    }
}
