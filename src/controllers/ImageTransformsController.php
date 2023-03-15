<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\helpers\Image;
use craft\models\ImageTransform;
use craft\validators\ColorValidator;
use craft\web\assets\edittransform\EditTransformAsset;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The ImageTransformsController class is a controller that handles various actions related to image transforms,
 * such as creating, editing and deleting transforms.
 * Note that all actions in the controller require an authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ImageTransformsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // All image transform actions require an admin
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Shows the image transform index.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $variables = [];

        $variables['transforms'] = Craft::$app->getImageTransforms()->getAllTransforms();
        $variables['modes'] = ImageTransform::modes();

        return $this->renderTemplate('settings/assets/transforms/_index.twig', $variables);
    }

    /**
     * Edit an image transform.
     *
     * @param string|null $transformHandle The transform’s handle, if any.
     * @param ImageTransform|null $transform The transform being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested transform cannot be found
     */
    public function actionEdit(?string $transformHandle = null, ?ImageTransform $transform = null): Response
    {
        if ($transform === null) {
            if ($transformHandle !== null) {
                $transform = Craft::$app->getImageTransforms()->getTransformByHandle($transformHandle);

                if (!$transform) {
                    throw new NotFoundHttpException('Transform not found');
                }
            } else {
                $transform = new ImageTransform();
            }
        }

        $this->getView()->registerAssetBundle(EditTransformAsset::class);

        if ($transform->id) {
            $title = trim($transform->name) ?: Craft::t('app', 'Edit Image Transform');
        } else {
            $title = Craft::t('app', 'Create a new image transform');
        }

        $qualityPickerOptions = [
            ['label' => Craft::t('app', 'Low'), 'value' => 10],
            ['label' => Craft::t('app', 'Medium'), 'value' => 30],
            ['label' => Craft::t('app', 'High'), 'value' => 60],
            ['label' => Craft::t('app', 'Very High'), 'value' => 80],
            ['label' => Craft::t('app', 'Maximum'), 'value' => 100],
        ];

        if ($transform->quality) {
            // Default to Low, even if quality is < 10
            $qualityPickerValue = 10;
            foreach ($qualityPickerOptions as $option) {
                if ($transform->quality >= $option['value']) {
                    $qualityPickerValue = $option['value'];
                } else {
                    break;
                }
            }
        } else {
            // Auto
            $qualityPickerValue = 0;
        }

        return $this->renderTemplate('settings/assets/transforms/_settings.twig', [
            'handle' => $transformHandle,
            'transform' => $transform,
            'title' => $title,
            'qualityPickerOptions' => $qualityPickerOptions,
            'qualityPickerValue' => $qualityPickerValue,
        ]);
    }

    /**
     * Saves an image transform.
     *
     * @return Response|null
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $transform = new ImageTransform();
        $transform->id = $this->request->getBodyParam('transformId');
        $transform->name = $this->request->getBodyParam('name');
        $transform->handle = $this->request->getBodyParam('handle');
        $transform->width = (int)$this->request->getBodyParam('width') ?: null;
        $transform->height = (int)$this->request->getBodyParam('height') ?: null;
        $transform->mode = $this->request->getBodyParam('mode');
        $transform->position = $this->request->getBodyParam('position');
        $transform->quality = $this->request->getBodyParam('quality') ?: null;
        $transform->interlace = $this->request->getBodyParam('interlace');
        $transform->format = $this->request->getBodyParam('format');
        $transform->fill = $this->request->getBodyParam('fill') ?: null;
        $transform->upscale = $this->request->getBodyParam('upscale', $transform->upscale);

        if (empty($transform->format)) {
            $transform->format = null;
        }

        // TODO: This validation should be handled on the transform object
        $errors = false;

        if (empty($transform->width) && empty($transform->height)) {
            $this->setFailFlash(Craft::t('app', 'You must set at least one of the dimensions.'));
            $errors = true;
        }

        if ($transform->quality && ($transform->quality > 100 || $transform->quality < 1)) {
            $this->setFailFlash(Craft::t('app', 'Quality must be a number between 1 and 100 (included).'));
            $errors = true;
        }

        if (!empty($transform->format) && !Image::isWebSafe($transform->format)) {
            $this->setFailFlash(Craft::t('app', 'That is not an allowed format.'));
            $errors = true;
        }

        if ($transform->mode === 'letterbox') {
            $transform->fill = $transform->fill ? ColorValidator::normalizeColor($transform->fill) : 'transparent';
        }

        if (!$errors) {
            $success = Craft::$app->getImageTransforms()->saveTransform($transform);
        } else {
            $success = false;
        }

        if (!$success) {
            return $this->asModelFailure($transform, modelName: 'transform');
        }

        return $this->asModelSuccess(
            $transform,
            Craft::t('app', 'Transform saved.'),
        );
    }

    /**
     * Deletes an image transform.
     *
     * @return Response
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $transformId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getImageTransforms()->deleteTransformById($transformId);

        return $this->asSuccess();
    }
}
