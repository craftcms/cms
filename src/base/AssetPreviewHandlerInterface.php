<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.0
 */

namespace craft\base;

/**
 * The AssetPreview interface dictates the requirements to register Asset Preview handlers with Craft
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
interface AssetPreviewHandlerInterface
{
    /**
     * Returns the asset preview HTML.
     *
     * @return string The preview modal HTML
     */
    public function getPreviewHtml(): string;
}
