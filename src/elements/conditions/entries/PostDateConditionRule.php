<?php

namespace craft\elements\conditions\entries;

use Craft;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\EntryQuery;

/**
 * Element post date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class PostDateConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Post Date');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['postDate', 'after', 'before'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var EntryQuery $query */
        $query->postDate($this->queryParamValue());
    }
}
