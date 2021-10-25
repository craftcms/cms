<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseTextOperatorConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use yii\db\QueryInterface;

/**
 * Slug condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SlugConditionRule extends BaseTextOperatorConditionRule implements QueryConditionRuleInterface
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
    public static function exclusiveQueryParams(): array
    {
        return ['slug'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        switch ($this->operator) {
            case self::OPERATOR_CONTAINS:
                $query->slug('= %' . Db::escapeParam($this->value) . '%');
                break;
            case self::OPERATOR_BEGINS_WITH:
                $query->slug('= ' . Db::escapeParam($this->value) . '%');
                break;
            case self::OPERATOR_ENDS_WITH:
                $query->slug('= %' . Db::escapeParam($this->value));
                break;
            default:
                $query->slug($this->operator . ' ' . Db::escapeParam($this->value)); //
        }
    }
}
