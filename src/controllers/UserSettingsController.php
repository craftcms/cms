<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\enums\CmsEdition;
use craft\models\UserGroup;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
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
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }


        $viewActions = ['edit-group'];
        if (in_array($action->id, $viewActions)) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        if ($action->id !== 'save-user-settings') {
            Craft::$app->requireEdition(CmsEdition::Team);
        }

        return true;
    }

    /**
     * Renders a user group’s edit screen.
     *
     * @param int|null $groupId
     * @param UserGroup|null $group
     * @return Response
     * @since 5.1.0
     */
    public function actionEditGroup(?int $groupId = null, ?UserGroup $group = null): Response
    {
        $this->requireCpRequest();

        if (Craft::$app->edition === CmsEdition::Team) {
            if (!$group) {
                $group = Craft::$app->getUserGroups()->getTeamGroup();
            }

            return $this->renderTemplate('settings/users/groups/_team.twig', [
                'group' => $group,
                'readOnly' => $this->readOnly,
            ]);
        }

        if (!$group) {
            if ($groupId) {
                $group = Craft::$app->getUserGroups()->getGroupById($groupId);
                if (!$group) {
                    throw new NotFoundHttpException("Invalid group ID: $groupId");
                }
            } else {
                $group = Craft::createObject(UserGroup::class);
            }
        }

        $crumbs = [
            ['label' => Craft::t('app', 'Settings'), 'url' => 'settings'],
            ['label' => Craft::t('app', 'Users'), 'url' => 'settings/users'],
            ['label' => Craft::t('app', 'User Groups'), 'url' => 'settings/users'],
        ];

        $formActions = [
            [
                'label' => Craft::t('app', 'Save and continue editing'),
                'redirect' => Craft::$app->getSecurity()->hashData('settings/users/groups/{id}'),
                'shortcut' => true,
                'retainScroll' => true,
            ],
        ];

        if ($group->id) {
            $title = trim($group->name) ?: Craft::t('app', 'Edit User Group');
        } else {
            $title = Craft::t('app', 'Create a new user group');
        }

        return $this->renderTemplate('settings/users/groups/_edit.twig', [
            'group' => $group,
            'crumbs' => $crumbs,
            'formActions' => $formActions,
            'title' => $title,
            'readOnly' => $this->readOnly,
        ]);
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

        if (Craft::$app->edition === CmsEdition::Team) {
            $group = Craft::$app->getUserGroups()->getTeamGroup();
        } else {
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
        }

        $isNewGroup = !$group->id;

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
        if (!$isNewGroup && is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (!$group->can($permission)) {
                    // Yep. This will require an elevated session
                    $this->requireElevatedSession();
                    break;
                }
            }
        }

        if (Craft::$app->edition === CmsEdition::Team) {
            $permissions[] = 'accessCp';
        } elseif ($isNewGroup) {
            // assignNewUserGroup => assignUserGroup:<uid>
            $assignNewGroupKey = array_search('assignNewUserGroup', $permissions);
            if ($assignNewGroupKey !== false) {
                $permissions[$assignNewGroupKey] = "assignUserGroup:$group->uid";
            }
        }

        Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions);

        $this->setSuccessFlash(Craft::$app->edition === CmsEdition::Team
            ? Craft::t('app', 'Permissions saved.')
            : Craft::t('app', 'Group saved.'));
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

        if (Craft::$app->edition->value >= CmsEdition::Team->value) {
            $settings['require2fa'] = $this->request->getBodyParam('require2fa') ?: false;
        }

        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
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
