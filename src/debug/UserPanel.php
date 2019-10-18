<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;

/**
 * Debugger panel that collects and displays user info..
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPanel extends \yii\debug\panels\UserPanel
{
    /**
     *
     */
    public function save()
    {
        $data = parent::save();

        if (isset($data['identity'])) {
            $security = Craft::$app->getSecurity();
            foreach ($data['identity'] as $key => $value) {
                $data['identity'][$key] = $security->redactIfSensitive($key, $value);
            }
        }

        return $data;
    }
}
