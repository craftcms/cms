<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\conditions\elements\fields;

use craft\conditions\QueryConditionRuleInterface;

/**
 * FieldConditionRuleInterface defines the common interface to be implemented by custom fields’ query condition rule classes.
 *
 * Classes implementing this interface should also use [[FieldConditionRuleTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface FieldConditionRuleInterface extends QueryConditionRuleInterface
{
    /**
     * Sets the UUID of the custom field associated with this rule.
     *
     * @param string $uid
     */
    public function setFieldUid(string $uid): void;

    /**
     * Returns the UUID of the custom field associated with this rule.
     *
     * @return string
     */
    public function getFieldUid(): string;
}
