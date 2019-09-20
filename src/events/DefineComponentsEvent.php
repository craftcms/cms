<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Define components event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @deprecated in 3.2.
 */
class DefineComponentsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The component definitions
     */
    public $components = [];
}
