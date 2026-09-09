<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Image\Events\ImageEditorSaving;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

readonly class ImageEditorController
{
    use RespondsWithFlash;

    public function __construct(
        private Assets $assets,
        private AssetTransformers $assetTransformers,
    ) {}

    public function show(Request $request): Response
    {
        $request->validate([
            'assetId' => ['required'],
        ]);

        $assetId = $request->input('assetId');
        $asset = $this->assets->getAssetById($assetId);

        abort_if(! $asset, 400, t('The asset you’re trying to edit does not exist.'));

        Gate::authorize('editImage', $asset);

        abort_if(! $asset->getSupportsImageEditor(), 400, 'Unsupported file format');

        $focal = $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null;

        return new JsonResponse([
            'html' => template('_special/image_editor'),
            'focalPoint' => $focal,
        ]);
    }

    public function editImage(Request $request): Response
    {
        $request->validate([
            'assetId' => ['required', 'integer'],
            'size' => ['required', 'integer'],
        ]);

        $assetId = $request->integer('assetId');
        $size = $request->integer('size');

        $asset = Asset::findOne($assetId);

        abort_if(! $asset, 400, "Invalid asset ID: $asset");

        Gate::authorize('editImage', $asset);

        abort_if(! $asset->getSupportsImageEditor(), 400, 'Unsupported file format');

        try {
            $url = $this->assets->getImagePreviewUrl($asset, $size, $size);

            return redirect($url);
        } catch (NotSupportedException) {
            // just output the file contents
            $path = ImageTransformHelper::getLocalImageSource($asset);

            return response()->file($path, [
                'Content-Disposition' => 'inline; filename="'.$asset->getFilename().'"',
            ]);
        }
    }

    public function save(Request $request, Elements $elements): Response
    {
        $request->validate([
            'assetId' => ['required'],
            'viewportRotation' => ['required', 'integer'],
            'imageRotation' => ['required', 'numeric'],
            'replace' => ['required'],
            'cropData' => ['required', 'array'],
        ]);

        $assetId = $request->input('assetId');
        $viewportRotation = $request->integer('viewportRotation');
        $imageRotation = $request->float('imageRotation');
        $replace = $request->boolean('replace');
        $cropData = $request->input('cropData');
        $focalPoint = $request->input('focalPoint');
        $imageDimensions = $request->input('imageDimensions');
        $flipData = $request->input('flipData');
        $zoom = (float) $request->float('zoom', 1);

        // avoid a potential division by zero error
        if (! $imageDimensions['width'] || ! $imageDimensions['height']) {
            abort(400, t('Invalid imageDimensions param'));
        }

        $asset = $this->assets->getAssetById($assetId);

        abort_if($asset === null, 400, 'The Asset cannot be found');

        $folder = $asset->getFolder();

        // Do what you want with your own photo.
        if ($asset->id !== $request->craftUser()?->asElement()->photoId) {
            Gate::authorize('editImage', $asset);
        }

        // Verify parameter adequacy
        abort_if(
            ! in_array($viewportRotation, [0, 90, 180, 270], false),
            400,
            t('Viewport rotation must be 0, 90, 180 or 270 degrees'),
        );

        if (
            is_array($cropData) &&
            array_diff(['offsetX', 'offsetY', 'height', 'width'], array_keys($cropData))
        ) {
            abort(400, t('Invalid cropping parameters passed'));
        }

        event($event = new ImageEditorSaving(
            asset: $asset,
            replace: $replace,
            viewportRotation: $viewportRotation,
            imageRotation: $imageRotation,
            cropData: $cropData,
            focalPoint: $focalPoint,
            imageDimensions: $imageDimensions,
            flipData: $flipData,
            zoom: $zoom,
        ));

        if ($event->handled) {
            return $this->asSuccess(data: array_filter([
                'newAssetId' => $event->newAssetId,
            ]));
        }

        $transformer = new ImageTransformer;

        $originalImageWidth = $asset->width;
        $originalImageHeight = $asset->height;

        $transformer->startImageEditing($asset);

        $imageCropped = ($cropData['width'] !== $imageDimensions['width'] || $cropData['height'] !== $imageDimensions['height']);
        $imageRotated = $viewportRotation !== 0 || $imageRotation !== 0.0;
        $imageFlipped = ! empty($flipData['x']) || ! empty($flipData['y']);
        $imageChanged = $imageCropped || $imageRotated || $imageFlipped;

        if ($imageFlipped) {
            $transformer->flipImage(! empty($flipData['x']), ! empty($flipData['y']));
        }

        $upscale = Cms::config()->upscaleImages;
        Cms::config()->upscaleImages = true;

        if ($zoom !== 1.0) {
            $transformer->scaleImage((int) ($originalImageWidth * $zoom), (int) ($originalImageHeight * $zoom));
        }

        Cms::config()->upscaleImages = $upscale;

        if ($imageRotated) {
            $transformer->rotateImage($imageRotation + $viewportRotation);
        }

        $imageCenterX = $transformer->getEditedImageWidth() / 2;
        $imageCenterY = $transformer->getEditedImageHeight() / 2;

        $adjustmentRatio = min($originalImageWidth / $imageDimensions['width'], $originalImageHeight / $imageDimensions['height']);
        $width = $cropData['width'] * $zoom * $adjustmentRatio;
        $height = $cropData['height'] * $zoom * $adjustmentRatio;
        $x = $imageCenterX + ($cropData['offsetX'] * $zoom * $adjustmentRatio) - $width / 2;
        $y = $imageCenterY + ($cropData['offsetY'] * $zoom * $adjustmentRatio) - $height / 2;

        $focal = null;

        if ($focalPoint) {
            $adjustmentRatio = min($originalImageWidth / $focalPoint['imageDimensions']['width'], $originalImageHeight / $focalPoint['imageDimensions']['height']);
            $fx = $imageCenterX + ($focalPoint['offsetX'] * $zoom * $adjustmentRatio) - $x;
            $fy = $imageCenterY + ($focalPoint['offsetY'] * $zoom * $adjustmentRatio) - $y;

            $focal = [
                'x' => $fx / $width,
                'y' => $fy / $height,
            ];
        }

        if ($imageCropped) {
            $transformer->crop((int) $x, (int) $y, (int) $width, (int) $height);
        }

        $finalImage = $imageChanged
            ? $transformer->finishImageEditing()
            : $transformer->cancelImageEditing();

        $output = [];

        if ($replace) {
            $oldFocal = $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null;
            $focalChanged = $focal !== $oldFocal;
            $asset->setFocalPoint($focal);

            if ($focalChanged) {
                $this->assetTransformers->invalidate($asset);
            }

            // Only replace file if it changed, otherwise just save changed focal points
            if ($imageChanged) {
                $this->assets->replaceAssetFile($asset, $finalImage, $asset->getFilename(), $asset->getMimeType());
            } elseif ($focalChanged) {
                $elements->saveElement($asset);
            }

            return $this->asSuccess(data: $output);
        }

        $newAsset = new Asset;
        $newAsset->avoidFilenameConflicts = true;
        $newAsset->ruleset->useScenario(AssetRules::SCENARIO_CREATE);

        $newAsset->tempFilePath = $finalImage;
        $newAsset->setFilename($asset->getFilename());
        $newAsset->newFolderId = $folder->id;
        $newAsset->setVolumeId($folder->volumeId);
        $newAsset->setFocalPoint($focal);

        // Don't validate required custom fields
        $elements->saveElement($newAsset);

        $output['newAssetId'] = $newAsset->id;

        return $this->asSuccess(data: $output);
    }

    public function updateFocalPoint(Request $request, Elements $elements): Response
    {
        $request->validate([
            'assetUid' => ['required', 'string'],
            'focal' => ['required'],
            'focalEnabled' => ['required'],
        ]);

        $assetUid = $request->input('assetUid');
        $focalData = $request->input('focal');
        $focalEnabled = $request->input('focalEnabled');

        // if focal point is disabled, set focal data to null
        if ($focalEnabled === false) {
            $focalData = null;
        }

        /** @var Asset|null $asset */
        $asset = Asset::find()->uid($assetUid)->one();

        abort_if(! $asset, 400, "Invalid asset UID: $assetUid");

        Gate::authorize('editImage', $asset);

        $asset->setFocalPoint($focalData);
        $elements->saveElement($asset);
        $this->assetTransformers->invalidate($asset);

        return $this->asSuccess();
    }
}
