<?php

namespace craft\conditions\elements\user;

use Craft;
use craft\conditions\BaseTextConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use yii\db\QueryInterface;

/**
 * Email condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Email extends BaseTextConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Email');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['email'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->email($this->paramValue());
    }
}
