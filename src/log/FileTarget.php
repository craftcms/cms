<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use craft\base\LogTargetTrait;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileTarget extends \yii\log\FileTarget
{
    use LogTargetTrait;
}
