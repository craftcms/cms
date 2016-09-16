<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\helpers\Image;
use craft\app\models\AssetTransform;
use craft\app\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The AssetTransformsController class is a controller that handles various actions related to asset transformations,
 * such as creating, editing and deleting transforms.
 *
 * Note that all actions in the controller require an authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
    }

    /**
     * Shows the asset transform list.
     *
     * @return string The rendering result
     */
    public function actionTransformIndex()
    {
        $variables['transforms'] = Craft::$app->getAssetTransforms()->getAllTransforms();
        $variables['transformModes'] = AssetTransform::getTransformModes();

        return $this->renderTemplate('settings/assets/transforms/_index', $variables);
    }

    /**
     * Edit an asset transform.
     *
     * @param string         $transformHandle The transform’s handle, if any.
     * @param AssetTransform $transform       The transform being edited, if there were any validation errors.
     *
     * @return string The rendering result
     * @throws NotFoundHttpException if the requested transform cannot be found
     */
    public function actionEditTransform($transformHandle = null, AssetTransform $transform = null)
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

        return $this->renderTemplate('settings/assets/transforms/_settings', [
            'handle' => $transformHandle,
            'transform' => $transform
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
        $transform->format = $request->getBodyParam('format');

        if (empty($transform->format)) {
            $transform->format = null;
        }

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

        if (!empty($transform->format) && !in_array($transform->format, Image::getWebSafeFormats())) {
            $session->setError(Craft::t('app', 'That is not an allowed format.'));
            $errors = true;
        }

        if (!$errors) {
            // Did it save?
            if (Craft::$app->getAssetTransforms()->saveTransform($transform)) {
                $session->setNotice(Craft::t('app', 'Transform saved.'));

                return $this->redirectToPostedUrl($transform);
            }

            $session->setError(Craft::t('app', 'Couldn’t save transform.'));
        }

        // Send the transform back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'transform' => $transform
        ]);

        return null;
    }

    /**
     * Deletes an asset transform.
     *
     * @return Response
     */
    public function actionDeleteTransform()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $transformId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getAssetTransforms()->deleteTransform($transformId);

        return $this->asJson(['success' => true]);
    }
}
