<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions\Contracts;

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use Illuminate\Database\Query\Builder;

/**
 * ElementQueryConditionRuleInterface defines the common interface to be implemented by element condition rule classes that apply to element queries.
 */
interface ElementQueryConditionRuleInterface extends ConditionRuleInterface
{
    /**
     * Applies a `where` condition to the query builder.
     *
     * @param  Builder  $query  The query builder
     * @param  ElementQueryInterface  $elementQuery  The element query
     */
    public function modifyQuery(Builder $query, ElementQueryInterface $elementQuery): void;
}
