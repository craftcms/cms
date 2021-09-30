<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;

/**
 * Debugger panel that collects and displays request data.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.1
 */
class RequestPanel extends \yii\debug\panels\RequestPanel
{
    /**
     * @inheritdoc
     */
    public function save(): array
    {
        $data = parent::save();
        return Craft::$app->getSecurity()->redactIfSensitive('', $data);
    }
}
