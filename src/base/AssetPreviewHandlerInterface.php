<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\NotSupportedException;

/**
 * AssetPreviewHandlerInterface defines the common interface to be implemented by classes that provide asset previewing functionality.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
interface AssetPreviewHandlerInterface
{
    /**
     * Returns the asset preview HTML.
     *
     * @param array $variables Additional variables to pass to the template.
     * @return string The preview modal HTML
     * @throws NotSupportedException if the asset can’t be previewed
     */
    public function getPreviewHtml(array $variables = []): string;
}
