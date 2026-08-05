<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions\Contracts;

use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * ElementConditionInterface defines the common interface to be implemented by element conditions.
 *
 * A base implementation is provided by [[ElementCondition]].
 *
 * @mixin ElementCondition
 *
 * @phpstan-require-extends ElementCondition
 */
interface ElementConditionInterface extends ConditionInterface
{
    /**
     * Returns the possible field layouts that the condition could be working with.
     *
     * @return FieldLayout[]
     */
    public function getFieldLayouts(): array;

    /**
     * Sets the possible field layouts that the condition could be working with.
     *
     * @param  array<FieldLayout|array<string, mixed>>  $fieldLayouts
     */
    public function setFieldLayouts(array $fieldLayouts): void;

    /**
     * Modifies a given query based on the configured condition rules.
     */
    public function modifyQuery(ElementQueryInterface $query): void;

    /**
     * Returns whether the given element matches the condition.
     */
    public function matchElement(ElementInterface $element): bool;
}
