<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Class AssetLogicException
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated in 6.0.0 use {@see \CraftCms\Cms\Asset\Exceptions\AssetOperationException} instead.
     */
    class AssetOperationException extends AssetException
    {
        /**
         * @return string the user-friendly name of this exception
         */
        public function getName(): string
        {
            return 'Asset Logic Error';
        }
    }
}

class_alias(\CraftCms\Cms\Asset\Exceptions\AssetOperationException::class, AssetOperationException::class);
