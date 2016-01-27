<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\Category;

/**
 * Category event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Category The category model associated with the event.
     */
    public $category;
}
