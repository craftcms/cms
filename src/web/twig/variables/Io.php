<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\variables;

use Craft;
use craft\helpers\Assets;
use craft\helpers\ConfigHelper;

/**
 * Io variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Io
{
    // Public Methods
    // =========================================================================

    /**
     * Return max upload size in bytes.
     *
     * @return int|float
     */
    public function getMaxUploadSize()
    {
        $maxUpload = ConfigHelper::sizeInBytes(ini_get('upload_max_filesize'));
        $maxPost = ConfigHelper::sizeInBytes(ini_get('post_max_size'));
        $memoryLimit = ConfigHelper::sizeInBytes(ini_get('memory_limit'));

        $uploadInBytes = min($maxUpload, $maxPost);

        if ($memoryLimit > 0) {
            $uploadInBytes = min($uploadInBytes, $memoryLimit);
        }

        $configLimit = Craft::$app->getConfig()->getGeneral()->maxUploadFileSize;

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
