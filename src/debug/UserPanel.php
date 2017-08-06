<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\debug;

use Craft;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecord;

/**
 * Debugger panel that collects and displays user info..
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserPanel extends \yii\debug\panels\UserPanel
{
    /**
     * @inheritdoc
     */
    public function save()
    {
        // Nearly identical to parent::save, except we redact any sensitive info from the panel.
        $user = Craft::$app->getUser();
        $data = $user->getIdentity();

        if ($data === null) {
            return null;
        }

        $authManager = Craft::$app->getAuthManager();
        $security = Craft::$app->getSecurity();

        $rolesProvider = null;
        $permissionsProvider = null;

        if ($authManager) {
            $rolesProvider = new ArrayDataProvider([
                'allModels' => $authManager->getRolesByUser($user->id),
            ]);

            $permissionsProvider = new ArrayDataProvider([
                'allModels' => $authManager->getPermissionsByUser($user->id),
            ]);
        }

        $attributes = array_keys(get_object_vars($data));

        if ($data instanceof ActiveRecord) {
            $attributes = array_keys($data->getAttributes());
        }

        foreach ($attributes as $key) {
            $data->$key = $security->redactIfSensitive($key, $data->$key);
        }

        return [
            'identity' => $data,
            'attributes' => $attributes,
            'rolesProvider' => $rolesProvider,
            'permissionsProvider' => $permissionsProvider,
        ];
    }
}
