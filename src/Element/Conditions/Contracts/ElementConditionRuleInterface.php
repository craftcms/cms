<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions\Contracts;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

/**
 * ElementConditionRuleInterface defines the common interface to be implemented by element condition rule classes.
 *
 * @property-read string[] $exclusiveQueryParams The query param names that this rule should have exclusive control over
 */
interface ElementConditionRuleInterface extends ConditionRuleInterface
{
    /**
     * Returns the query param names that this rule should have exclusive control over.
     *
     * @return string[]
     */
    public function getExclusiveQueryParams(): array;

    /**
     * Modifies the given query with the condition rule.
     */
    public function modifyQuery(ElementQueryInterface $query): void;

    /**
     * Returns whether the given element matches the condition rule.
     */
    public function matchElement(ElementInterface $element): bool;
}
