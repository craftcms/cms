<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class OperationAbortedException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class OperationAbortedException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Operation aborted';
    }
}
