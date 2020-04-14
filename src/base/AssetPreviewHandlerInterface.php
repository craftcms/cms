<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.0
 */

namespace craft\base;

use yii\base\NotSupportedException;

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
     * @throws NotSupportedException if the asset can’t be previewed
     */
    public function getPreviewHtml(): string;
}
