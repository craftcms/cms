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
     * Class FsObjectNotFoundException
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see CraftCms\Cms\Filesystem\Exceptions\FsObjectNotFoundException} instead.
     */
    class FsObjectNotFoundException extends FilesystemException
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
