<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Describable defines the common interface to be implemented by components that
 * have description within their chips.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.8.0
 */
interface Describable
{
    /**
     * Returns the component’s description.
     *
     * @return string|null
     */
    public function getDescription(): ?string;
}
