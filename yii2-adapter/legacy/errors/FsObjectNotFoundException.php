<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;

/**
 * Class FsObjectNotFoundException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsObjectNotFoundException extends FilesystemException
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Filesystem object not found';
    }
}
