<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\CategoryGroup;
use yii\base\Event;

/**
 * Category group event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CategoryGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var CategoryGroup|null The category group model associated with the event.
     */
    public $categoryGroup;

    /**
     * @var bool Whether the category group is brand new
     */
    public $isNew = false;
}
