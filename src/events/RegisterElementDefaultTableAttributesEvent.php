<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementDefaultTableAttributesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterElementDefaultTableAttributesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The selected source’s key
     */
    public $source;

    /**
     * @var string[] List of registered default table attributes for the element type.
     */
    public $tableAttributes = [];
}
