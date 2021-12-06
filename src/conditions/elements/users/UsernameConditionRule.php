<?php

namespace craft\conditions\elements\users;

use Craft;
use craft\conditions\BaseTextConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use yii\db\QueryInterface;

/**
 * Username condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class UsernameConditionRule extends BaseTextConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Username');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['username'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->username($this->paramValue());
    }
}
