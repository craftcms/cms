<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\enums\Color;

/**
 * Colorable defines the common interface to be implemented by components that
 * can have colors within the control panel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface Colorable
{
    /**
     * Returns the HTML for the component’s thumbnail, if it has one.
     *
     * @return Color|null
     */
    public function getColor(): ?Color;
}
