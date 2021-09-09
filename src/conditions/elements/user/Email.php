<?php

namespace craft\conditions\elements\user;

use Craft;
use craft\conditions\BaseTextOperatorConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use yii\db\QueryInterface;

/**
 * Email condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Email extends BaseTextOperatorConditionRule implements ElementQueryConditionRuleInterface
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
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var UserQuery $query */
        $query->email('=' . ' ' . $this->value);
    }
}
