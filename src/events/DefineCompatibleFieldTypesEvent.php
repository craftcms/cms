<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

/**
 * DefineCompatibleFieldTypesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.7
 */
class DefineCompatibleFieldTypesEvent extends FieldEvent
{
    /**
     * @var string[] The field type classes that are considered compatible with [[$field]].
     */
    public array $compatibleTypes;
}
