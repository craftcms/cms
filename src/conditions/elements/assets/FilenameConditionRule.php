<?php

namespace craft\conditions\elements\assets;

use Craft;
use craft\conditions\BaseTextConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\AssetQuery;
use yii\db\QueryInterface;

/**
 * Filename condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FilenameConditionRule extends BaseTextConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Filename');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['filename'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var AssetQuery $query */
        $query->filename($this->paramValue());
    }
}
