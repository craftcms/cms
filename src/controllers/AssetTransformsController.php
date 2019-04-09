<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Image;
use craft\models\AssetTransform;
use craft\web\assets\edittransform\EditTransformAsset;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The AssetTransformsController class is a controller that handles various actions related to asset transformations,
 * such as creating, editing and deleting transforms.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetTransformsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All asset transform actions require an admin
        $this->requireAdmin();

        parent::init();
    }

    /**
     * Shows the asset transform list.
     *
     * @return Response
     */
    public function actionTransformIndex(): Response
    {
        $variables = [];

        $variables['transforms'] = Craft::$app->getAssetTransforms()->getAllTransforms();
        $variables['modes'] = AssetTransform::modes();

        return $this->renderTemplate('settings/assets/transforms/_index', $variables);
    }

    /**
     * Edit an asset transform.
     *
     * @param string|null $transformHandle The transform’s handle, if any.
     * @param AssetTransform|null $transform The transform being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested transform cannot be found
     */
    public function actionEditTransform(string $transformHandle = null, AssetTransform $transform = null): Response
    {
        if ($transform === null) {
            if ($transformHandle !== null) {
                $transform = Craft::$app->getAssetTransforms()->getTransformByHandle($transformHandle);

                if (!$transform) {
                    throw new NotFoundHttpException('Transform not found');
                }
            } else {
                $transform = new AssetTransform();
            }
        }

        $this->getView()->registerAssetBundle(EditTransformAsset::class);

        if ($transform->id) {
            $title = trim($transform->name) ?: Craft::t('app', 'Edit Image Transform');
        } else {
            $title = Craft::t('app', 'Create a new image transform');
        }

        return $this->renderTemplate('settings/assets/transforms/_settings', [
            'handle' => $transformHandle,
            'transform' => $transform,
            'title' => $title,
        ]);
    }

    /**
     * Saves an asset transform.
     *
     * @return Response|null
     */
    public function actionSaveTransform()
    {
        $this->requirePostRequest();

        $transform = new AssetTransform();
        $request = Craft::$app->getRequest();
        $transform->id = $request->getBodyParam('transformId');
        $transform->name = $request->getBodyParam('name');
        $transform->handle = $request->getBodyParam('handle');
        $transform->width = $request->getBodyParam('width');
        $transform->height = $request->getBodyParam('height');
        $transform->mode = $request->getBodyParam('mode');
        $transform->position = $request->getBodyParam('position');
        $transform->quality = $request->getBodyParam('quality');
        $transform->interlace = $request->getBodyParam('interlace');
        $transform->format = $request->getBodyParam('format');

        if (empty($transform->format)) {
            $transform->format = null;
        }

        // TODO: This validation should be handled on the transform object
        $errors = false;

        $session = Craft::$app->getSession();
        if (empty($transform->width) && empty($transform->height)) {
            $session->setError(Craft::t('app', 'You must set at least one of the dimensions.'));
            $errors = true;
        }

        if (!empty($transform->quality) && (!is_numeric($transform->quality) || $transform->quality > 100 || $transform->quality < 1)) {
            $session->setError(Craft::t('app', 'Quality must be a number between 1 and 100 (included).'));
            $errors = true;
        }

        if (empty($transform->quality)) {
            $transform->quality = null;
        }

        if (!empty($transform->format) && !in_array($transform->format, Image::webSafeFormats(), true)) {
            $session->setError(Craft::t('app', 'That is not an allowed format.'));
            $errors = true;
        }

        if (!$errors) {
            $success = Craft::$app->getAssetTransforms()->saveTransform($transform);
        } else {
            $success = false;
        }

        if (!$success) {
            // Send the transform back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'transform' => $transform
            ]);

            return null;
        }

        $session->setNotice(Craft::t('app', 'Transform saved.'));

        return $this->redirectToPostedUrl($transform);
    }

    /**
     * Deletes an asset transform.
     *
     * @return Response
     */
    public function actionDeleteTransform(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $transformId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getAssetTransforms()->deleteTransformById($transformId);

        return $this->asJson(['success' => true]);
    }
}
