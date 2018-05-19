<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\volumes\Local;
use craft\volumes\MissingVolume;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The VolumeController class is a controller that handles various actions related to asset volumes, such as
 * creating, editing, renaming and reordering them.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class VolumesController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All asset volume actions require an admin
        $this->requireAdmin();
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

        /** @var Volume $volume */
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

        /** @var string[] $allVolumeTypes */
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
            $title = $volume->name;
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

        $tabs = [
            'settings' => [
                'label' => Craft::t('app', 'Settings'),
                'url' => '#assetvolume-settings'
            ],
            'fieldlayout' => [
                'label' => Craft::t('app', 'Field Layout'),
                'url' => '#assetvolume-fieldlayout'
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
            'tabs' => $tabs
        ]);
    }

    /**
     * Saves an asset volume.
     *
     * @return Response|null
     */
    public function actionSaveVolume()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $volumes = Craft::$app->getVolumes();

        $type = $request->getBodyParam('type');

        /** @var Volume $volume */
        $volume = $volumes->createVolume([
            'id' => $request->getBodyParam('volumeId'),
            'type' => $type,
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'hasUrls' => (bool)$request->getBodyParam('hasUrls'),
            'url' => $request->getBodyParam('url'),
            'settings' => $request->getBodyParam('types.'.$type)
        ]);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        $session = Craft::$app->getSession();

        if (!$volumes->saveVolume($volume)) {
            $session->setError(Craft::t('app', 'Couldn’t save volume.'));

            // Send the volume back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'volume' => $volume
            ]);

            return null;
        }

        $session->setNotice(Craft::t('app', 'Volume saved.'));

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

        $volumeIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
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

        $volumeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getVolumes()->deleteVolumeById($volumeId);

        return $this->asJson(['success' => true]);
    }
}
