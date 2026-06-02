<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Field;
use craft\base\FsInterface;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\helpers\Cp;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\models\Volume;
use craft\web\Controller;
use Illuminate\Support\Collection;
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
    private bool $readOnly;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $viewActions = ['volume-index', 'edit-volume'];
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
     * Shows the asset volume list.
     *
     * @return Response
     */
    public function actionVolumeIndex(): Response
    {
        $variables = [];
        $variables['volumes'] = Craft::$app->getVolumes()->getAllVolumes();
        $variables['readOnly'] = $this->readOnly;

        return $this->renderTemplate('settings/assets/volumes/_index.twig', $variables);
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
        if ($volumeId === null && $this->readOnly) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $volumesServices = Craft::$app->getVolumes();

        if ($volume === null) {
            if ($volumeId !== null) {
                $volume = $volumesServices->getVolumeById($volumeId);

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

        $fsHandle = $volume->getFsHandle();
        $allVolumes = $volumesServices->getAllVolumes();
        /** @var Collection<string> $takenFsHandles */
        $takenFsHandles = Collection::make($allVolumes)
            ->filter(fn(Volume $volume) => !$volume->getSubpath())
            ->map(fn(Volume $volume) => $volume->getFsHandle());
        $fsOptions = Collection::make(Craft::$app->getFs()->getAllFilesystems())
            ->map(fn(FsInterface $fs) => [
                'label' => Craft::t('site', $fs->name),
                'value' => $fs->handle,
                'disabled' => Assets::isTempUploadFs($fs) || ($takenFsHandles->contains($fs->handle) && $fs->handle !== $fsHandle),
            ])
            ->sortBy(fn(array $option) => $option['label'])
            ->all();
        array_unshift($fsOptions, ['label' => Craft::t('app', 'Select a filesystem'), 'value' => '']);

        $response = $this->asCpScreen()
            ->title($title)
            ->addCrumb(Craft::t('app', 'Settings'), 'settings')
            ->addCrumb(Craft::t('app', 'Assets'), 'settings/assets')
            ->addCrumb(Craft::t('app', 'Volumes'), 'settings/assets')
            ->contentTemplate('settings/assets/volumes/_edit.twig', [
                'volumeId' => $volumeId,
                'volume' => $volume,
                'isNewVolume' => $isNewVolume,
                'typeName' => Asset::displayName(),
                'lowerTypeName' => Asset::lowerDisplayName(),
                'fsOptions' => $fsOptions,
                'readOnly' => $this->readOnly,
            ]);

        if (!$this->readOnly) {
            $response
                ->action('volumes/save-volume')
                ->redirectUrl('settings/assets')
                ->saveShortcutRedirectUrl('settings/assets/volumes/{id}')
                ->addAltAction(Craft::t('app', 'Save and continue editing'), [
                    'redirect' => 'settings/assets/volumes/{id}',
                    'shortcut' => true,
                    'retainScroll' => true,
                ])
                ->editUrl($volume->getCpEditUrl());
        } else {
            $response->noticeHtml(Cp::readOnlyNoticeHtml());
        }

        return $response;
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

        // prepare subpath for saving
        $subpath = $this->request->getBodyParam('subpath');
        if (!empty($subpath)) {
            $subpath = FileHelper::normalizePath(ltrim(trim($subpath), '/'));
        }
        $volume = new Volume([
            'id' => $volumeId,
            'uid' => $oldVolume->uid ?? null,
            'sortOrder' => $oldVolume->sortOrder ?? null,
            'name' => $this->request->getBodyParam('name'),
            'handle' => $this->request->getBodyParam('handle'),
            'fsHandle' => $this->request->getBodyParam('fsHandle'),
            'subpath' => $subpath ?? null,
            'transformFsHandle' => $this->request->getBodyParam('transformFsHandle'),
            'transformSubpath' => $this->request->getBodyParam('transformSubpath', ""),
            'titleTranslationMethod' => $this->request->getBodyParam('titleTranslationMethod', Field::TRANSLATION_METHOD_SITE),
            'titleTranslationKeyFormat' => $this->request->getBodyParam('titleTranslationKeyFormat'),
            'altTranslationMethod' => $this->request->getBodyParam('altTranslationMethod', Field::TRANSLATION_METHOD_NONE),
            'altTranslationKeyFormat' => $this->request->getBodyParam('altTranslationKeyFormat'),
        ]);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        if (!$volumesService->saveVolume($volume)) {
            return $this->asModelFailure($volume, Craft::t('app', 'Couldn’t save volume.'), 'volume');
        }

        return $this->asModelSuccess($volume, Craft::t('app', 'Volume saved.'), 'volume');
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

        return $this->asSuccess();
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

        return $this->asSuccess();
    }
}
