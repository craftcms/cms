<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\CategoryGroup;

/**
 * Category group event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CategoryGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var CategoryGroup The category group model associated with the event.
     */
    public $categoryGroup;
}
