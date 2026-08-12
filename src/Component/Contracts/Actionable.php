<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Contracts;

use CraftCms\Cms\Cp\Html\MenuHtml;

/**
 * Actionable defines the common interface to be implemented by components that
 * can have action menus within the control panel.
 */
interface Actionable
{
    /**
     * Returns action menu items for the component.
     *
     * See [[\CraftCms\Cms\Cp\Html\MenuHtml::disclosureMenu()]] for documentation on supported item properties.
     *
     * By default, all non-destructive items will be included in chips and cards. Individual items can explicitly
     * opt into/out of being shown within chips and cards by including a `showInChips` key.
     *
     * ```php
     * 'showInChips' => false,
     * ```
     *
     * @see MenuHtml::disclosureMenu()
     */
    /** @return list<array<string, mixed>> */
    public function getActionMenuItems(): array;
}
