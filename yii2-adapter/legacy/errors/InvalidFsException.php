<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;

/**
 * Class InvalidVolumeException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class InvalidFsException extends FilesystemException
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Invalid filesystem';
    }
}
