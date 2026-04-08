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
     * Class ElementNotFoundException
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated in 6.0.0 use {@see \CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException} instead.
     */
    class ElementNotFoundException extends Exception
    {
        /**
         * @return string the user-friendly name of this exception
         */
        public function getName(): string
        {
            return 'Element not found';
        }
    }
}

class_alias(\CraftCms\Cms\Element\Queries\Exceptions\ElementNotFoundException::class, ElementNotFoundException::class);
