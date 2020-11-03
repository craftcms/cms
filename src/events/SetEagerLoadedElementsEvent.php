<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * SetEagerLoadedElementsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class SetEagerLoadedElementsEvent extends Event
{
    /**
     * @var string The handle that was used to eager-load the elements
     */
    public $handle;

    /**
     * @param ElementInterface[] $elements The eager-loaded elements
     */
    public $elements;
}
