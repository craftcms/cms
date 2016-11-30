<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

/**
 * Class FileHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FileHelper extends \yii\helpers\FileHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function normalizePath($path, $ds = DIRECTORY_SEPARATOR)
    {
        // Is this a UNC network share path?
        $isUnc = (strncmp($path, '//', 2) === 0 || strncmp($path, '\\\\', 2) === 0);

        // Normalize the path
        $path = parent::normalizePath($path, $ds);

        // If it is UNC, add those slashes back in front
        if ($isUnc) {
            $path = $ds.$ds.ltrim($path, $ds);
        }

        return $path;
    }
}
