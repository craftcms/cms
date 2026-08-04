<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Fs;
use craft\base\FsInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\Response as YiiResponse;

/**
 * The FsController class is a controller that handles various actions related to asset filesystems, such as
 * creating, editing, renaming and deleting them.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FsController extends Controller
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

        $viewActions = ['index', 'edit'];
        if (in_array($action->id, $viewActions)) {
            // Some actions require admin but not allowAdminChanges
            $this->requireAdmin(false);
        } else {
            // All other actions require an admin & allowAdminChanges
            $this->requireAdmin();
        }

        $this->readOnly = !Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return true;
    }

    /**
     * Shows the filesystem list.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $variables = [];
        $variables['filesystems'] = Craft::$app->getFs()->getAllFilesystems();
        $variables['readOnly'] = $this->readOnly;

        return $this->renderTemplate('settings/filesystems/_index.twig', $variables);
    }

    /**
     * Edit a filesystem.
     *
     * @param string|null $handle The filesystem’s handle, if editing an existing filesystem.
     * @param Fs|null $filesystem The filesystem being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not an admin
     * @throws NotFoundHttpException if the requested volume cannot be found
     */
    public function actionEdit(?string $handle = null, ?Fs $filesystem = null): Response
    {
        if ($handle === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $fsService = Craft::$app->getFs();

        if ($filesystem === null) {
            if ($handle !== null) {
                $filesystem = $fsService->getFilesystemByHandle($handle);

                if ($filesystem === null) {
                    throw new NotFoundHttpException('Filesystem not found');
                }
            }
        }

        $allFsTypes = Craft::$app->getFs()->getAllFilesystemTypes();

        $fsInstances = [];
        $fsOptions = [];

        foreach ($allFsTypes as $fsType) {
            /** @var Fs $fsInstance */
            $fsInstance = Craft::createObject($fsType);

            if ($filesystem === null) {
                $filesystem = $fsInstance;
            }

            $fsInstances[$fsType] = $fsInstance;
            $fsOptions[] = [
                'value' => $fsType,
                'label' => $fsInstance::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($fsOptions, 'label');

        if ($handle && $fsService->getFilesystemByHandle($handle)) {
            $title = trim($filesystem->name ?: Craft::t('app', 'Edit Filesystem'));
        } else {
            $title = Craft::t('app', 'Create a new filesystem');
        }

        $response = $this->asCpScreen()
            ->title($title)
            ->addCrumb(Craft::t('app', 'Settings'), 'settings')
            ->addCrumb(Craft::t('app', 'Filesystems'), 'settings/filesystems')
            ->contentTemplate('settings/filesystems/_edit.twig', [
                'oldHandle' => $handle,
                'filesystem' => $filesystem,
                'fsOptions' => $fsOptions,
                'fsInstances' => $fsInstances,
                'fsTypes' => $allFsTypes,
                'readOnly' => $this->readOnly,
            ]);

        if (!$this->readOnly) {
            $response
                ->action('fs/save')
                ->redirectUrl('settings/filesystems')
                ->addAltAction(Craft::t('app', 'Save and continue editing'), [
                    'redirect' => 'settings/filesystems/{handle}',
                    'shortcut' => true,
                    'retainScroll' => true,
                ]);
        } else {
            $response->noticeHtml(Cp::readOnlyNoticeHtml());
        }

        return $response;
    }

    /**
     * Saves a filesystem.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSave(): ?YiiResponse
    {
        $this->requirePostRequest();

        $fsService = Craft::$app->getFs();
        $type = $this->request->getBodyParam('type');

        /** @var FsInterface|Fs $fs */
        $fs = $fsService->createFilesystem([
            'type' => $type,
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'oldHandle' => $this->request->getBodyParam('oldHandle'),
            'settings' => $this->request->getBodyParam(sprintf('types.%s', Html::id($type))),
        ]);

        if (!$fsService->saveFilesystem($fs)) {
            return $this->asModelFailure($fs, Craft::t('app', 'Couldn’t save filesystem.'), 'filesystem');
        }

        return $this->asModelSuccess($fs, Craft::t('app', 'Filesystem saved.'), 'filesystem');
    }

    /**
     * Removes a filesystem.
     */
    public function actionRemove(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $handle = $this->request->getRequiredBodyParam('id');
        $fsService = Craft::$app->getFs();
        $fs = $fsService->getFilesystemByHandle($handle);

        if ($fs) {
            $fsService->removeFilesystem($fs);
        }

        return $this->asSuccess();
    }
}
