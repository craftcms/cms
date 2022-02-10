<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Field;
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
     * @param Volume|null $volume The volume being edited, if there were any validation errors.
     * @return Response
     * @throws ForbiddenHttpException if the user is not an admin
     * @throws NotFoundHttpException if the requested volume cannot be found
     */
    public function actionEditVolume(?int $volumeId = null, ?Volume $volume = null): Response
    {
        $this->requireAdmin();

        $volumes = Craft::$app->getVolumes();

        if ($volume === null) {
            if ($volumeId !== null) {
                $volume = $volumes->getVolumeById($volumeId);

                if ($volume === null) {
                    throw new NotFoundHttpException('Volume not found');
                }
            } else {
                $volume = Craft::createObject(Volume::class);
            }
        }

        $isNewVolume = !$volume->id;

        if ($isNewVolume) {
            $title = Craft::t('app', 'Create a new asset volume');
        } else {
            $title = trim($volume->name) ?: Craft::t('app', 'Edit Volume');
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
                'label' => Craft::t('app', 'Volumes'),
                'url' => UrlHelper::url('settings/assets'),
            ],
        ];

        return $this->renderTemplate('settings/assets/volumes/_edit', [
            'volumeId' => $volumeId,
            'volume' => $volume,
            'isNewVolume' => $isNewVolume,
            'title' => $title,
            'crumbs' => $crumbs,
            'typeName' => Asset::displayName(),
            'lowerTypeName' => Asset::lowerDisplayName(),
        ]);
    }

    /**
     * Saves an asset volume.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSaveVolume(): ?Response
    {
        $this->requirePostRequest();

        $volumesService = Craft::$app->getVolumes();
        $volumeId = $this->request->getBodyParam('volumeId') ?: null;

        if ($volumeId) {
            $oldVolume = $volumesService->getVolumeById($volumeId);
            if (!$oldVolume) {
                throw new BadRequestHttpException("Invalid volume ID: $volumeId");
            }
        }

        $volume = new Volume([
            'id' => $volumeId,
            'uid' => $oldVolume->uid ?? null,
            'sortOrder' => $oldVolume->sortOrder ?? null,
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'fsHandle' => $this->request->getBodyParam('fsHandle'),
            'titleTranslationMethod' => $this->request->getBodyParam('titleTranslationMethod', Field::TRANSLATION_METHOD_SITE),
            'titleTranslationKeyFormat' => $this->request->getBodyParam('titleTranslationKeyFormat'),
        ]);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        if (!$volumesService->saveVolume($volume)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save volume.'));

            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'volume' => $volume,
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
