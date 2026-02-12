<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Identifiable defines the common interface to be implemented by components that
 * can be identified by an ID.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface Identifiable
{
    /**
     * Returns the ID of the component, which should be used as the value of hidden inputs.
     *
     * @return string|int|null
     */
    public function getId(): string|int|null;
}
