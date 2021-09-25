<?php

namespace craft\conditions\elements\user;

use Craft;
use craft\conditions\BaseTextOperatorConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use craft\helpers\Db;
use yii\db\QueryInterface;

/**
 * Email condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Email extends BaseTextOperatorConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Email');
    }

    /**
     * @inheritdoc
     */
    public static function exclusiveQueryParams(): array
    {
        return ['email'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->email(Db::escapeParam($this->value));
    }
}
