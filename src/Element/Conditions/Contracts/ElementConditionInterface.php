<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace CraftCms\Cms\Element\Conditions\Contracts;

use craft\base\ElementInterface;
use CraftCms\Cms\Condition\Contracts\ConditionInterface;
use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

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
     * @return \CraftCms\Cms\FieldLayout\FieldLayout[]
     */
    public function getFieldLayouts(): array;

    /**
     * Sets the possible field layouts that the condition could be working with.
     *
     * @param  array<\CraftCms\Cms\FieldLayout\FieldLayout|array>  $fieldLayouts
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
