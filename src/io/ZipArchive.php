<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\io;

use Craft;

/**
 * Class ZipArchive
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ZipArchive
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function unzip($srcZip, $destFolder)
    {
        Craft::$app->getConfig()->maxPowerCaptain();

        $zip = new \ZipArchive();
        $zipContents = $zip->open($srcZip, \ZipArchive::CHECKCONS);

        if ($zipContents !== true) {
            Craft::error('Could not open the zip file: '.$srcZip, __METHOD__);

            return false;
        }

        $success = $zip->extractTo($destFolder);
        $zip->close();

        if (!$success) {
            Craft::error('Could not extract the zip file at '.$srcZip.' to '.$destFolder, __METHOD__);

            return false;
        }

        return true;
    }
}
