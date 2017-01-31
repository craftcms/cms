<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class EtException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EtException extends Exception
{
    // $code = 10001 is that the config/ folder isn't writable.
}
