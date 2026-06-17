<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

/**
 * Interface AllowableInSandbox
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.18.3
 */
interface AllowableInSandbox
{
    /**
     * Returns whether the given method is allowed to be called by sandboxed Twig templates.
     */
    public function methodAllowedInSandbox(string $method): bool;

    /**
     * Returns whether the given properly is allowed to be referenced by sandboxed Twig templates.
     */
    public function propertyAllowedInSandbox(string $property): bool;
}
