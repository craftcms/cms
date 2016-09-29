<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\base\Volume;
use craft\app\elements\Asset;
use craft\app\helpers\Json;
use craft\app\helpers\Url;
use craft\app\web\Controller;
use Exception;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The VolumeController class is a controller that handles various actions related to asset volumes, such as
 * creating, editing, renaming and reordering them.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @return string The rendering result
     */
    public function actionVolumeIndex()
    {
        $variables['volumes'] = Craft::$app->getVolumes()->getAllVolumes();

        return $this->renderTemplate('settings/assets/volumes/_index', $variables);
    }

    /**
     * Edit an asset volume.
     *
     * @param integer $volumeId The volume’s ID, if editing an existing volume.
     * @param Volume  $volume   The volume being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested volume cannot be found
     */
    public function actionEditVolume($volumeId = null, Volume $volume = null)
    {
        $this->requireAdmin();

        $volumes = Craft::$app->getVolumes();
        if ($volume === null) {
            if ($volumeId !== null) {
                $volume = $volumes->getVolumeById($volumeId);

                if (!$volume) {
                    throw new NotFoundHttpException('Volume not found');
                }
            } else {
                $volume = $volumes->createVolume(\craft\app\volumes\Local::class);
            }
        }

        if (Craft::$app->getEdition() == Craft::Pro) {
            /** @var Volume[] $allVolumeTypes */
            $allVolumeTypes = $volumes->getAllVolumeTypes();
            $volumeInstances = [];
            $volumeTypeOptions = [];

            foreach ($allVolumeTypes as $class) {
                if ($class === $volume->getType() || $class::isSelectable()) {
                    $volumeInstances[$class] = $volumes->createVolume($class);

                    $volumeTypeOptions[] = [
                        'value' => $class,
                        'label' => $class::displayName()
                    ];
                }
            }
        } else {
            $volumeTypeOptions = [];
            $volumeInstances = [];
            $allVolumeTypes = null;
        }

        $isNewVolume = !$volume->id;

        if ($isNewVolume) {
            $title = Craft::t('app', 'Create a new asset volume');
        } else {
            $title = $volume->name;
        }

        $crumbs = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => Url::getUrl('settings')
            ],
            [
                'label' => Craft::t('app', 'Assets'),
                'url' => Url::getUrl('settings/assets')
            ],
            [
                'label' => Craft::t('app', 'Volumes'),
                'url' => Url::getUrl('settings/assets')
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
            'volumeInstances' => $volumeInstances,
            'title' => $title,
            'crumbs' => $crumbs,
            'tabs' => $tabs
        ]);
    }

    /**
     * Saves an asset volume.
     *
     * @return Response
     */
    public function actionSaveVolume()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $volumes = Craft::$app->getVolumes();

        if (Craft::$app->getEdition() == Craft::Pro) {
            $type = $request->getBodyParam('type');
        } else {
            $type = \craft\app\volumes\Local::class;
        }

        /** @var Volume $volume */
        $volume = $volumes->createVolume([
            'id' => $request->getBodyParam('volumeId'),
            'type' => $type,
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'hasUrls' => $request->getBodyParam('hasUrls'),
            'url' => $request->getBodyParam('url'),
            'settings' => $request->getBodyParam('types.'.$type)
        ]);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Asset::class;
        $volume->setFieldLayout($fieldLayout);

        $session = Craft::$app->getSession();
        if ($volumes->saveVolume($volume)) {
            $session->setNotice(Craft::t('app', 'Volume saved.'));

            return $this->redirectToPostedUrl();
        }

        $session->setError(Craft::t('app', 'Couldn’t save volume.'));

        // Send the volume back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'volume' => $volume
        ]);

        return null;
    }

    /**
     * Reorders asset volumes.
     *
     * @return Response
     */
    public function actionReorderVolumes()
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
    public function actionDeleteVolume()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $volumeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getVolumes()->deleteVolumeById($volumeId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Load Assets VolumeType data.
     *
     * This is used to, for example, load Amazon S3 bucket list or Rackspace Cloud Storage Containers.
     *
     * @return Response
     */
    public function actionLoadVolumeTypeData()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $volumeType = $request->getRequiredBodyParam('volumeType');
        $dataType = $request->getRequiredBodyParam('dataType');
        $params = $request->getBodyParam('params');

        $volumeType = 'craft\app\volumes\\'.$volumeType;

        if (!class_exists($volumeType)) {
            return $this->asErrorJson(Craft::t('app', 'The volume type specified does not exist!'));
        }

        try {
            $result = call_user_func_array(
                [
                    $volumeType,
                    'load'.ucfirst($dataType)
                ],
                $params);

            return $this->asJson($result);
        } catch (Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }
    }
}
