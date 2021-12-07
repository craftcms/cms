<?php

namespace craft\conditions\elements\users;

use Craft;
use craft\conditions\BaseLightswitchConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use yii\db\QueryInterface;

/**
 * Admin condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class AdminConditionRule extends BaseLightswitchConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Admin');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['admin'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->admin($this->value);
    }
}
