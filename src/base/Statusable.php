<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Statusable defines the common interface to be implemented by components that
 * can have statuses within the control panel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface Statusable
{
    /**
     * Returns all the possible statuses that components may have.
     *
     * It should return an array whose keys are the status values, and values are the human-facing status labels, or an array
     * with the following keys:
     *
     * - **`label`** – The human-facing status label.
     * - **`color`** – The status color. See [[craft\enums\Color]] for possible values.
     *
     * @return array
     */
    public static function statuses(): array;

    /**
     * Returns the component’s status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;
}
