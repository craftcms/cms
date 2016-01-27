<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\base;

use Craft;

/**
 * WidgetTrait implements the common methods and properties for dashboard widget classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
trait WidgetTrait
{
    // Properties
    // =========================================================================

    /**
     * @var integer The ID of the user that owns the widget
     */
    public $userId;

    /**
     * @var integer The widget’s sort order
     */
    public $sortOrder;
}
