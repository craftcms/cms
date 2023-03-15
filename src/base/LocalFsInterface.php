<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * LocalFsInterface is the interface that must be implemented by all filesystems that operate locally.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface LocalFsInterface
{
    /**
     * Return the root path of the FS.
     *
     * @return string
     * @todo anything that calls this will need to account for volume subpaths when that's a thing
     */
    public function getRootPath(): string;
}
