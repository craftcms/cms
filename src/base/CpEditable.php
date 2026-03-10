<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\web\twig\AllowedInSandbox;

/**
 * CpEditable defines the common interface to be implemented by components that
 * have a dedicated edit page in the control panel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface CpEditable
{
    /**
     * Returns the URL to the component’s edit page in the control panel.
     *
     * @return string|null
     */
    #[AllowedInSandbox]
    public function getCpEditUrl(): ?string;
}
