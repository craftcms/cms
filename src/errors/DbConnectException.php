<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\errors;

use Craft;
use yii\base\UserException;

/**
 * Class DbConnectException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DbConnectException extends UserException
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return Craft::t('app', 'Database Connection Exception');
    }
}
