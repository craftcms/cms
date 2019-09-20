<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use craft\base\MissingComponentInterface;
use craft\base\MissingComponentTrait;
use craft\base\Widget;

/**
 * MissingWidget represents a widget with an invalid class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
