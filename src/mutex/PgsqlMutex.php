<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 * @deprecated in 3.7.30. Use [[Mutex]] instead, with [[Mutex::$mutex|$mutex]] set to `'yii\mutex\PgsqlMutex'`.
 */
class PgsqlMutex extends \yii\mutex\PgsqlMutex
{
    use MutexTrait;
}
