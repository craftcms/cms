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
 * @deprecated in 3.7.30. Use [[Mutex]] instead, with [[Mutex::$mutex|$mutex]] set to a reliable multi-server
 * mutex driver, such as `yii\redis\Mutex` (provided by [yii2-redis](https://github.com/yiisoft/yii2-redis)).
 */
class MysqlMutex extends \yii\mutex\MysqlMutex
{
    use MutexTrait;
}
