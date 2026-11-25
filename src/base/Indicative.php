<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\enums\Color;

/**
 * Indicative defines the common interface to be implemented by components that
 * have indicator icons within their chips.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.6.0
 */
interface Indicative
{
    /**
     * Returns the component’s indicators.
     *
     * Each indicator should be a nested array with the following keys:
     *
     * - `label` – The indicator label.
     * - `icon` – The indicator icon name. System icons can be found in `src/icons/solid/`.
     * - `iconColor` – The color of the icon.
     *
     * @return array{label:string,icon:string,iconColor?:Color|string}[]
     */
    public function getIndicators(): array;
}
