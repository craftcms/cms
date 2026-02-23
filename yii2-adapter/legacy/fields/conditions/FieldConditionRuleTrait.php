<?php

namespace craft\fields\conditions;

use CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * FieldConditionRuleTrait implements the common methods and properties for custom fields' query condition rule classes.
     *
     * @property ElementConditionInterface $condition
     * @method ElementConditionInterface getCondition()
     * @property-write string $fieldUid The UUID of the custom field associated with this rule
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Conditions\FieldConditionRuleTrait} instead.
     * @phpstan-ignore trait.unused
     */
    trait FieldConditionRuleTrait
    {
    }
}

class_alias(\CraftCms\Cms\Field\Conditions\FieldConditionRuleTrait::class, FieldConditionRuleTrait::class);
