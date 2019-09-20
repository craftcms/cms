<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementSearchableAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterElementSearchableAttributesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of registered searchable attributes for the element type.
     */
    public $attributes = [];
}
