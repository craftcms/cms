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
interface AssetPreviewInterface
{

    // Public Methods
    // =========================================================================


    /**
     * Returns the preview's modal HTML.
     *
     * @return string|false The preview’s modal HTML
     */
    public function getModalHtml();

    /**
     * Returns HTML to be added to the head of the page on preview
     *
     * @return string|false the preview's head HTML
     */
    public function getHeadHtml();

    /**
     * Returns HTML to be added at the end of the page before the closing body tag
     *
     * @return string|false the preview's head HTML
     */
    public function getFootHtml();
}
