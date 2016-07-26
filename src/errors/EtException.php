<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\errors;

use yii\base\Exception;

/**
 * Class EtException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EtException extends Exception
{
    // $code = 10001 is that the craft/config folder isn't writable.
}
