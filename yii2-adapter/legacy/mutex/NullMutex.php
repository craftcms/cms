<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

use yii\mutex\Mutex as YiiMutex;

/**
 * NullMutex provides a [[YiiMutex|mutex]] implementation that doesn’t actually do anything.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.30
 * @deprecated in 6.0.0. Use `\Illuminate\Support\Facades\Cache::lock()` instead.
 */
class NullMutex extends YiiMutex
{
    /**
     * @inheritdoc
     */
    protected function acquireLock($name, $timeout = 0): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function releaseLock($name): bool
    {
        return true;
    }
}
