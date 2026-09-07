<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Conditions;

use CraftCms\Cms\Condition\BaseDateRangeConditionRule;
use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Contracts\Database\Query\Builder;

use function CraftCms\Cms\t;

/**
 * Element post date condition rule.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 */
class PostDateConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface, ElementQueryConditionRuleInterface
{
    public function getLabel(): string
    {
        return t('Post Date');
    }

    public function modifyQuery(Builder $query, ElementQuery $elementQuery): void
    {
        EntryQuery::applyPostDate($query, $this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var Entry $element */
        return $this->matchValue($element->postDate);
    }
}
