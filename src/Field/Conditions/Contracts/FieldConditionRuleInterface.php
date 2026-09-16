<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Conditions\Contracts;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface;
use CraftCms\Cms\Element\Conditions\Contracts\ElementQueryConditionRuleInterface;
use CraftCms\Cms\Field\Conditions\FieldConditionRuleTrait;

/**
 * FieldConditionRuleInterface defines the common interface to be implemented by custom fields’ query condition rule classes.
 *
 * Classes implementing this interface should also implement {@see ElementConditionRuleInterface} and/or {@see ElementQueryConditionRuleInterface}, and use {@see FieldConditionRuleTrait}.
 */
interface FieldConditionRuleInterface
{
    /**
     * Returns the UUID of the custom field associated with this rule.
     */
    public function getFieldUid(): string;

    /**
     * Sets the UUID of the custom field associated with this rule.
     */
    public function setFieldUid(string $uid): void;

    /**
     * Sets the UUID of the custom field layout element associated with this rule.
     */
    public function setLayoutElementUid(?string $uid): void;
}
