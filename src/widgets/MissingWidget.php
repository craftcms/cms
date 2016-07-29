<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\widgets;

use craft\app\base\Widget;
use craft\app\base\MissingComponentInterface;
use craft\app\base\MissingComponentTrait;

/**
 * MissingWidget represents a widget with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class MissingWidget extends Widget implements MissingComponentInterface
{
    // Traits
    // =========================================================================

    use MissingComponentTrait;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getBodyHtml()
    {
        return false;
    }
}
