<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use CraftCms\Cms\Support\Facades\Security;

/**
 * Debugger panel that collects and displays user info..
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPanel extends \yii\debug\panels\UserPanel
{
    /**
     * @inheritdoc
     */
    public function save()
    {
        $data = parent::save();

        if (isset($data['identity'])) {
            foreach ($data['identity'] as $key => $value) {
                $data['identity'][$key] = Security::redactIfSensitive($key, $value);
            }
        }

        return $data;
    }
}
