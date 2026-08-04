<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Class FsObjectExistsException
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see CraftCms\Cms\Filesystem\Exceptions\FsObjectExistsException} instead.
     */
    class FsObjectExistsException extends FilesystemException
    {
        /**
         * {@inheritdoc}
         */
        public function getName(): string
        {
            return 'Filesystem object exists';
        }
    }
}
