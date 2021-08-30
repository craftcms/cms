<?php

namespace craft\conditions\elements\user;

use Craft;
use craft\conditions\BaseTextValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\UserQuery;
use yii\db\QueryInterface;

/**
 *
 */
class Email extends BaseTextValueConditionRule implements ElementQueryConditionRuleInterface
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Email');
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        /** @var UserQuery $query */
        return $query->email($this->value);
    }
}