<?php

namespace craft\conditions\elements\assets;

use Craft;
use craft\conditions\BaseDateRangeConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\AssetQuery;
use yii\db\QueryInterface;

/**
 * Date Modified condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class DateModifiedConditionRule extends BaseDateRangeConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'File Modification Date');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['dateModified'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->dateModified($this->paramValue());
    }
}
