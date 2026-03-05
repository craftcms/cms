<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\NotSupportedException;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * AssetPreviewHandlerInterface defines the common interface to be implemented by classes that provide asset previewing functionality.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.4.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface} instead.
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
}

class_alias(\CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface::class, AssetPreviewHandlerInterface::class);
