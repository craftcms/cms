<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\App as AppHelper;
use craft\helpers\Assets;

/**
 * Io variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Io
{
    // Public Methods
    // =========================================================================

    /**
     * Return max upload size in bytes.
     *
     * @return int
     */
    public function getMaxUploadSize(): int
    {
        $maxUpload = AppHelper::phpConfigValueInBytes('upload_max_filesize');
        $maxPost = AppHelper::phpConfigValueInBytes('post_max_size');
        $memoryLimit = AppHelper::phpConfigValueInBytes('memory_limit');

        $uploadInBytes = min($maxUpload, $maxPost);

        if ($memoryLimit > 0) {
            $uploadInBytes = min($uploadInBytes, $memoryLimit);
        }

        $configLimit = (int)Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;

        if ($configLimit) {
            $uploadInBytes = min($uploadInBytes, $configLimit);
        }

        return $uploadInBytes;
    }

    /**
     * Returns a list of file kinds.
     *
     * @return array
     */
    public function getFileKinds(): array
    {
        return Assets::getFileKinds();
    }
}
