<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\conditions;

use craft\base\conditions\ConditionRuleInterface;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * ElementConditionRuleInterface defines the common interface to be implemented by element condition rule classes.
 *
 * @property-read string[] $exclusiveQueryParams The query param names that this rule should have exclusive control over
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
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
     *
     * @param ElementQueryInterface $query
     */
    public function modifyQuery(ElementQueryInterface $query): void;

    /**
     * Returns whether the given element matches the condition rule.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function matchElement(ElementInterface $element): bool;
}
