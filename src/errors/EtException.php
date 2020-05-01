<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class EtException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EtException extends Exception
{
    // $code = 10001 is that the config/ folder isn't writable.

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'ET Exception';
    }
}
