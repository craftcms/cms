<?php

namespace craft\conditions\elements;

use Craft;
use craft\conditions\BaseTextConditionRule;
use craft\conditions\QueryConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use yii\db\QueryInterface;

/**
 * Slug condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class SlugConditionRule extends BaseTextConditionRule implements QueryConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Slug');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['slug'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->slug($this->paramValue());
    }
}
