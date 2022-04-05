<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;

/**
 * Mutation populate event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class MutationPopulateElementEvent extends ElementEvent
{
    /**
     * @var array The arguments used to populate element with data
     */
    public $arguments;

    /**
     * @var ElementInterface The element being populated with data
     */
    public $element;
}
