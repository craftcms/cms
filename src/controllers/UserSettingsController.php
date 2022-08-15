<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\models\UserGroup;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The UserSettingsController class is a controller that handles various user group and user settings related tasks such as
 * creating, editing and deleting user groups and saving Craft user settings.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserSettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All user settings actions require an admin
        $this->requireAdmin();

        if ($action->id !== 'save-user-settings') {
            Craft::$app->requireEdition(Craft::Pro);
        }

        return parent::beforeAction($action);
    }

    /**
     * Saves a user group.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveGroup(): ?Response
    {
        $this->requirePostRequest();

        $groupId = $this->request->getBodyParam('groupId');

        if ($groupId) {
            $group = Craft::$app->getUserGroups()->getGroupById($groupId);
            if (!$group) {
                throw new BadRequestHttpException('User group not found');
            }
        } else {
            $group = new UserGroup();
        }

        $group->name = $this->request->getBodyParam('name');
        $group->handle = $this->request->getBodyParam('handle');
        $group->description = $this->request->getBodyParam('description');

        // Did it save?
        if (!Craft::$app->getUserGroups()->saveGroup($group)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save group.'));

            // Send the group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'group' => $group,
            ]);

            return null;
        }

        // Save the new permissions
        $permissions = $this->request->getBodyParam('permissions', []);

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

        // assignNewUserGroup => assignUserGroup:<uid>
        if (!$groupId) {
            $assignNewGroupKey = array_search('assignNewUserGroup', $permissions);
            if ($assignNewGroupKey !== false) {
                $permissions[$assignNewGroupKey] = "assignUserGroup:$group->uid";
            }
        }

        Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions);

        $this->setSuccessFlash(Craft::t('app', 'Group saved.'));
        return $this->redirectToPostedUrl($group);
    }

    /**
     * Deletes a user group.
     *
     * @return Response
     */
    public function actionDeleteGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $groupId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getUserGroups()->deleteGroupById($groupId);

        return $this->asSuccess();
    }

    /**
     * Saves the system user settings.
     *
     * @return Response|null
     */
    public function actionSaveUserSettings(): ?Response
    {
        $this->requirePostRequest();
        $projectConfig = Craft::$app->getProjectConfig();
        $settings = $projectConfig->get('users') ?? [];

        $photoVolumeId = $this->request->getBodyParam('photoVolumeId');

        $settings['photoVolumeUid'] = $photoVolumeId ? Craft::$app->getVolumes()->getVolumeById($photoVolumeId)?->uid : null;
        $settings['photoSubpath'] = $this->request->getBodyParam('photoSubpath') ?: null;

        if (Craft::$app->getEdition() === Craft::Pro) {
            $settings['requireEmailVerification'] = (bool)$this->request->getBodyParam('requireEmailVerification');
            $settings['validateOnPublicRegistration'] = (bool)$this->request->getBodyParam('validateOnPublicRegistration');
            $settings['allowPublicRegistration'] = (bool)$this->request->getBodyParam('allowPublicRegistration');
            $settings['deactivateByDefault'] = (bool)$this->request->getBodyParam('deactivateByDefault');
            $settings['defaultGroup'] = $this->request->getBodyParam('defaultGroup');
        }

        $projectConfig->set('users', $settings, 'Update user settings');

        $this->setSuccessFlash(Craft::t('app', 'User settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
