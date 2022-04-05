<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\UserException;

/**
 * Class DbConnectException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class DbConnectException extends UserException
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Database Connection Error';
    }
}
