<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseTextValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use yii\db\QueryInterface;

/**
 *
 */
class Slug extends BaseTextValueConditionRule implements ElementQueryConditionRuleInterface
{
    public static function displayName(): string
    {
        return Craft::t('app', 'Slug');
    }

    /**
     * @inheritDoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        return $query->slug($this->value);
    }
}