<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\web\twig\AllowedInSandbox;

/**
 * Thumbable defines the common interface to be implemented by components that
 * can have thumbnails within the control panel.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
interface Thumbable
{
    /**
     * Returns the HTML for the component’s thumbnail, if it has one.
     *
     * @param int $size The maximum width and height the thumbnail should have.
     * @return string|null
     */
    #[AllowedInSandbox]
    public function getThumbHtml(int $size): ?string;
}
