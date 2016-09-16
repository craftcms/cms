<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\models\UserGroup;
use craft\app\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

Craft::$app->requireEdition(Craft::Pro);

/**
 * The UserSettingsController class is a controller that handles various user group and user settings related tasks such as
 * creating, editing and deleting user groups and saving Craft user settings.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserSettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All user settings actions require an admin
        $this->requireAdmin();
    }

    /**
     * Saves a user group.
     *
     * @return Response|void
     * @throws NotFoundHttpException if the requested user group cannot be found
     */
    public function actionSaveGroup()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $groupId = $request->getBodyParam('groupId');

        if ($groupId) {
            $group = Craft::$app->getUserGroups()->getGroupById($groupId);

            if (!$group) {
                throw new NotFoundHttpException('User group not found');
            }
        } else {
            $group = new UserGroup();
        }

        $group->name = $request->getBodyParam('name');
        $group->handle = $request->getBodyParam('handle');

        // Did it save?
        if (Craft::$app->getUserGroups()->saveGroup($group)) {
            // Save the new permissions
            $permissions = $request->getBodyParam('permissions', []);

            // See if there are any new permissions in here
            if ($groupId && is_array($permissions)) {
                foreach ($permissions as $permission) {
                    if (!$group->can($permission)) {
                        // Yep. This will require an elevated session
                        $this->requireElevatedSession();
                        break;
                    }
                }
            }

            Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions);

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Group saved.'));

            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save group.'));

        // Send the group back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'group' => $group
        ]);

        return null;
    }

    /**
     * Deletes a user group.
     *
     * @return Response
     */
    public function actionDeleteGroup()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $groupId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getUserGroups()->deleteGroupById($groupId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Saves the system user settings.
     *
     * @return Response|null
     */
    public function actionSaveUserSettings()
    {
        $this->requirePostRequest();

        $settings['requireEmailVerification'] = (bool)Craft::$app->getRequest()->getBodyParam('requireEmailVerification');
        $settings['allowPublicRegistration'] = (bool)Craft::$app->getRequest()->getBodyParam('allowPublicRegistration');
        $settings['defaultGroup'] = Craft::$app->getRequest()->getBodyParam('defaultGroup');
        $settings['photoVolumeId'] = Craft::$app->getRequest()->getBodyParam('photoVolumeId');

        if (Craft::$app->getSystemSettings()->saveSettings('users', $settings)
        ) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'User settings saved.'));

            return $this->redirectToPostedUrl();
        }

        Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user settings.'));

        // Send the settings back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'settings' => $settings
        ]);

        return null;
    }
}
