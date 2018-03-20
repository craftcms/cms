<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use yii\base\Exception;

/**
 * Class Exception
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class QueryAbortedException extends Exception
{
    /**
     * @return string The user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Query Aborted Exception';
    }
}
