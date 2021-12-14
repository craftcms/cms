<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Field;
use craft\base\Fs;
use craft\base\FsInterface;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Volume;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The VolumeController class is a controller that handles various actions related to asset filesystems, such as
 * creating, editing, renaming and deleting them.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class FilesystemsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All asset volume actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Shows the asset volume list.
     *
     * @return Response
     */
    public function actionFilesystemIndex(): Response
    {
        $variables = [];
        $variables['filesystems'] = Craft::$app->getFilesystems()->getAllFilesystems();

        return $this->renderTemplate('settings/assets/filesystems/_index', $variables);
    }

    /**
     * Edit a filesystem.
     *
     * @param int|null $filesystemId The filesystem’s ID, if editing an existing filesystem.
     * @param Fs|null $filesystem The filesystem being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not an admin
     * @throws NotFoundHttpException if the requested volume cannot be found
     */
    public function actionEditFilesystem(?int $filesystemId = null, ?Fs $filesystem = null): Response
    {
        $this->requireAdmin();

        $filesystems = Craft::$app->getFilesystems();

        if ($filesystem === null) {
            if ($filesystemId !== null) {
                $filesystem = $filesystems->getFilesystemById($filesystemId);

                if ($filesystem === null) {
                    throw new NotFoundHttpException('Filesystem not found');
                }
            }
        }

        /** @var FsInterface[] $allFs */
        $allFsTypes = Craft::$app->getFilesystems()->getAllFilesystemTypes();

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

        $isNewFilesystem = !$filesystem->id;

        if ($isNewFilesystem) {
            $title = Craft::t('app', 'Create a new filesystem');
        } else {
            $title = trim($filesystem->name) ?: Craft::t('app', 'Edit Filesystem');
        }

        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('app', 'Assets'),
                'url' => UrlHelper::url('settings/assets'),
            ],
            [
                'label' => Craft::t('app', 'Filesystems'),
                'url' => UrlHelper::url('settings/assets/filesystems'),
            ],
        ];

        return $this->renderTemplate('settings/assets/filesystems/_edit', [
            'filesystemId' => $filesystemId,
            'filesystem' => $filesystem,
            'isNewFilesystem' => $isNewFilesystem,
            'fsOptions' => $fsOptions,
            'fsInstances' => $fsInstances,
            'fsTypes' => $allFsTypes,
            'title' => $title,
            'crumbs' => $crumbs,
        ]);
    }

    /**
     * Saves a filesystem.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveFilesystem(): ?Response
    {
        $this->requirePostRequest();

        $filesystemService = Craft::$app->getFilesystems();
        $type = $this->request->getBodyParam('type');
        $filesystemId = $this->request->getBodyParam('filesystemId') ?: null;

        if ($filesystemId) {
            $existingFilesystem = $filesystemService->getFilesystemById($filesystemId);
            if (!$existingFilesystem) {
                throw new BadRequestHttpException("Invalid filesystem ID: $filesystemId");
            }
        }

        $filesystem = $filesystemService->createFilesystem([
            'id' => $filesystemId,
            'uid' => $existingFilesystem->uid ?? null,
            'type' => $type,
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'hasUrls' => (bool)$this->request->getBodyParam('hasUrls'),
            'url' => $this->request->getBodyParam('url'),
            'settings' => $this->request->getBodyParam('types.' . $type),
        ]);

        if (!$filesystemService->saveFilesystem($filesystem)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save filesystem.'));

            // Send the filesystem back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'filesystem' => $filesystem,
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Filesystem saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Reorders asset volumes.
     *
     * @return Response
     */
    public function actionReorderVolumes(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $volumeIds = Json::decode($this->request->getRequiredBodyParam('ids'));
        Craft::$app->getVolumes()->reorderVolumes($volumeIds);

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes an asset volume.
     *
     * @return Response
     */
    public function actionDeleteVolume(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $volumeId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getVolumes()->deleteVolumeById($volumeId);

        return $this->asJson(['success' => true]);
    }
}
