<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\conditions;

use craft\base\conditions\ConditionInterface;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\models\FieldLayout;

/**
 * ElementConditionInterface defines the common interface to be implemented by element conditions.
 *
 * A base implementation is provided by [[ElementCondition]].
 *
 * @mixin ElementCondition
 * @phpstan-require-extends ElementCondition
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ElementConditionInterface extends ConditionInterface
{
    /**
     * Returns the possible field layouts that the condition could be working with.
     *
     * @return FieldLayout[]
     * @since 5.0.0
     */
    public function getFieldLayouts(): array;

    /**
     * Sets the possible field layouts that the condition could be working with.
     *
     * @param array<FieldLayout|array> $fieldLayouts
     * @since 5.1.0
     */
    public function setFieldLayouts(array $fieldLayouts): void;

    /**
     * Modifies a given query based on the configured condition rules.
     *
     * @param ElementQueryInterface $query
     */
    public function modifyQuery(ElementQueryInterface $query): void;

    /**
     * Returns whether the given element matches the condition.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function matchElement(ElementInterface $element): bool;
}
