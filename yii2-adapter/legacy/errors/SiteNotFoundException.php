<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Exceptions\SiteNotFoundException} instead.
     */
    class SiteNotFoundException extends Exception
    {
        /**
         * @return string the user-friendly name of this exception
         */
        public function getName(): string
        {
            return 'Site not found';
        }
    }
}

class_alias(\CraftCms\Cms\Site\Exceptions\SiteNotFoundException::class, SiteNotFoundException::class);
