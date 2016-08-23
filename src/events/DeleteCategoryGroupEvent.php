<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\CategoryGroup;

/**
 * Delete category group event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteCategoryGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var CategoryGroup The category group model associated with the event.
     */
    public $categoryGroup;
}
