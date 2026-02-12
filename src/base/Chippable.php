<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Chippable defines the common interface to be implemented by components that
 * can be displayed as chips in the control panel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface Chippable extends Identifiable
{
    /**
     * Returns a component by its ID.
     *
     * @param string|int $id
     * @return static|null
     */
    public static function get(string|int $id): ?static;

    /**
     * Returns what the component should be called within the control panel.
     *
     * @return string
     */
    public function getUiLabel(): string;
}
