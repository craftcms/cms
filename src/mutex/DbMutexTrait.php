<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

class_exists(MutexTrait::class);

if (false) {
    /**
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.7.30
     * @mixin Mutex
     * @deprecated in 3.7.30. Use [[MutexTrait]] instead.
     */
    trait DbMutexTrait
    {
    }
}
