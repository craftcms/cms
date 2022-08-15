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
 * @since 3.0.0
 */
class CategoryGroupEvent extends Event
{
    /**
     * @var CategoryGroup The category group model associated with the event.
     */
    public CategoryGroup $categoryGroup;

    /**
     * @var bool Whether the category group is brand new
     */
    public bool $isNew = false;
}
