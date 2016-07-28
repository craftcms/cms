<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements;

use craft\app\base\Element;
use craft\app\base\InvalidComponentInterface;
use craft\app\base\InvalidComponentTrait;

/**
 * InvalidElement represents an element with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class InvalidElement extends Element implements InvalidComponentInterface
{
    // Traits
    // =========================================================================

    use InvalidComponentTrait;

    // Public Methods
    // =========================================================================
}
