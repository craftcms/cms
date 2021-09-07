<?php

namespace craft\conditions\elements\entry;

use Craft;
use craft\conditions\BaseTextValueConditionRule;
use craft\conditions\elements\ElementQueryConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use yii\db\QueryInterface;

/**
 * Slug condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Slug extends BaseTextValueConditionRule implements ElementQueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Slug');
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): QueryInterface
    {
        /** @var ElementQueryInterface $query */
        return $query->slug($this->operator . Db::escapeParam($this->value));
    }
}
