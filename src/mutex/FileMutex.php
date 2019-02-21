<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

use Craft;
use craft\helpers\FileHelper;
use yii\base\Exception;

/**
 * @inheritdoc
 * @see Mutex
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 * @deprecated in 3.1.14. Use [[\yii\mutex\FileMutex]] instead.
 */
class FileMutex extends \yii\mutex\FileMutex
{
}
