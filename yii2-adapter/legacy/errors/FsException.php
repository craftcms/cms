<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class FsException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsException extends Exception
{
    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return 'Filesystem Error';
    }
}
