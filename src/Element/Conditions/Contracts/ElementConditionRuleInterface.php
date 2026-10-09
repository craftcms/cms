<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Conditions\Contracts;

use CraftCms\Cms\Condition\Contracts\ConditionRuleInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * ElementConditionRuleInterface defines the common interface to be implemented by element condition rule classes that apply to element instances.
 */
interface ElementConditionRuleInterface extends ConditionRuleInterface
{
    /**
     * Returns whether the given element matches the condition rule.
     */
    public function matchElement(ElementInterface $element): bool;
}
