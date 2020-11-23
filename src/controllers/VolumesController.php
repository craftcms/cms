<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\VolumeInterface;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\volumes\Local;
use craft\volumes\MissingVolume;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The VolumeController class is a controller that handles various actions related to asset volumes, such as
 * creating, editing, renaming and reordering them.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class VolumesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
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
    public function actionVolumeIndex(): Response
    {
        $variables = [];
        $variables['volumes'] = Craft::$app->getVolumes()->getAllVolumes();

        return $this->renderTemplate('settings/assets/volumes/_index', $variables);
    }

    /**
     * Edit an asset volume.
     *
     * @param int|null $volumeId The volume’s ID, if editing an existing volume.
     * @param VolumeInterface|null $volume The volume being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not an admin
     * @throws NotFoundHttpException if the requested volume cannot be found
     */
    public function actionEditVolume(int $volumeId = null, VolumeInterface $volume = null): Response
    {
        $this->requireAdmin();

        $volumes = Craft::$app->getVolumes();

        $missingVolumePlaceholder = null;

        if ($volume === null) {
            if ($volumeId !== null) {
                $volume = $volumes->getVolumeById($volumeId);

                if ($volume === null) {
                    throw new NotFoundHttpException('Volume not found');
                }

                if ($volume instanceof MissingVolume) {
                    $missingVolumePlaceholder = $volume->getPlaceholderHtml();
                    $volume = $volume->createFallback(Local::class);
                }
            } else {
                $volume = $volumes->createVolume(Local::class);
            }
        }

        /** @var string[]|VolumeInterface[] $allVolumeTypes */
        $allVolumeTypes = $volumes->getAllVolumeTypes();

        // Make sure the selected volume class is in there
        if (!in_array(get_class($volume), $allVolumeTypes, true)) {
            $allVolumeTypes[] = get_class($volume);
        }

        $volumeInstances = [];
        $volumeTypeOptions = [];

        foreach ($allVolumeTypes as $class) {
            if ($class === get_class($volume) || $class::isSelectable()) {
                $volumeInstances[$class] = $volumes->createVolume($class);

                $volumeTypeOptions[] = [
                    'value' => $class,
                    'label' => $class::displayName()
                ];
            }
        }

        // Sort them by name
        ArrayHelper::multisort($volumeTypeOptions, 'label');

        $isNewVolume = !$volume->id;

        if ($isNewVolume) {
            $title = Craft::t('app', 'Create a new asset volume');
        } else {
            $title = trim($volume->name) ?: Craft::t('app', 'Edit Volume');
        }

        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('app', 'Assets'),
                'url' => UrlHelper::url('settings/assets')
            ],
            [
                'label' => Craft::t('app', 'Volumes'),
                'url' => UrlHelper::url('settings/assets')
            ],
        ];

        return $this->renderTemplate('settings/assets/volumes/_edit', [
            'volumeId' => $volumeId,
            'volume' => $volume,
            'isNewVolume' => $isNewVolume,
            'volumeTypes' => $allVolumeTypes,
            'volumeTypeOptions' => $volumeTypeOptions,
            'missingVolumePlaceholder' => $missingVolumePlaceholder,
            'volumeInstances' => $volumeInstances,
            'title' => $title,
            'crumbs' => $crumbs,
        ]);
    }

    /**
     * Saves an asset volume.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveVolume()
    {
        $this->requirePostRequest();

        $volumesService = Craft::$app->getVolumes();
        $type = $this->request->getBodyParam('type');
        $volumeId = $this->request->getBodyParam('volumeId') ?: null;

        if ($volumeId) {
            $savedVolume = $volumesService->getVolumeById($volumeId);
            if (!$savedVolume) {
                throw new BadRequestHttpException("Invalid volume ID: $volumeId");
            }
        }

        $volumeData = [
            'id' => $volumeId,
            'uid' => $savedVolume->uid ?? null,
            'sortOrder' => $savedVolume->sortOrder ?? null,
            'type' => $type,
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'hasUrls' => (bool)$this->request->getBodyParam('hasUrls'),
            'url' => $this->request->getBodyParam('url'),
            'settings' => $this->request->getBodyParam('types.' . $type)
        ];

        $volume = $volumesService->createVolume($volumeData);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        if (!$volumesService->saveVolume($volume)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save volume.'));

            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'volume' => $volume
            ]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'Volume saved.'));
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
