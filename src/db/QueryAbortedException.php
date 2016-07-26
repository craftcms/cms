<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\db;

use yii\base\Exception;

/**
 * Class Exception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class QueryAbortedException extends Exception
{
    /**
     * @return string The user-friendly name of this exception
     */
    public function getName()
    {
        return 'Query Aborted Exception';
    }
}
