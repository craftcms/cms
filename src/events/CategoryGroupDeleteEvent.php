<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\CategoryGroup;

/**
 * Category group delete event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryGroupDeleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var CategoryGroup The category group model associated with the event.
     */
    public $categoryGroup;
}
