<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Asset\Assets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * The markup `Craft.AssetImageEditor` builds its modal from.
 */
class ImageEditorModalController
{
    public function __invoke(Request $request, Assets $assets): JsonResponse
    {
        $request->validate([
            'assetId' => ['required'],
        ]);

        $asset = $assets->getAssetById($request->input('assetId'));

        abort_if(!$asset, 400, t('The asset you’re trying to edit does not exist.'));

        Gate::authorize('editImage', $asset);

        abort_if(!$asset->getSupportsImageEditor(), 400, 'Unsupported file format');

        return new JsonResponse([
            'html' => template('yii2-adapter/_special/image_editor'),
            'focalPoint' => $asset->getHasFocalPoint() ? $asset->getFocalPoint() : null,
        ]);
    }
}
