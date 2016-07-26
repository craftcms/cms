<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\log;

use craft\app\helpers\Logging;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FileTarget extends \yii\log\FileTarget
{
    /**
     * @inheritdoc
     */
    protected function getContextMessage()
    {
        $message = parent::getContextMessage();

        // Remove any sensitive info.
        return Logging::redact($message);
    }
}
