<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\helpers\Cp;

/**
 * Actionable defines the common interface to be implemented by components that
 * can have action menus within the control panel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface Actionable
{
    /**
     * Returns action menu items for the component.
     *
     * See [[\craft\helpers\Cp::disclosureMenu()]] for documentation on supported item properties.
     *
     * By default, all non-destructive items will be included in chips and cards. Individual items can explicitly
     * opt into/out of being shown within chips and cards by including a `showInChips` key.
     *
     * ```php
     * 'showInChips' => false,
     * ```
     *
     * @return array
     * @see Cp::disclosureMenu()
     */
    public function getActionMenuItems(): array;
}
