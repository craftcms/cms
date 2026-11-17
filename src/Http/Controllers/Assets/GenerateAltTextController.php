<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\Analysis\ImageAnalyzer;
use CraftCms\Cms\Asset\Analysis\Tasks\GenerateAltText;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\RespondsWithFlash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Suggests alt text for an image in one site, for the alt text field to fill in. Nothing is saved.
 *
 * @since 6.0.0
 */
readonly class GenerateAltTextController
{
    use RespondsWithFlash;

    public function __invoke(Request $request, ImageAnalyzer $analyzer): JsonResponse
    {
        $request->validate([
            'assetId' => ['required', 'integer'],
            'siteId' => ['required', 'integer'],
        ]);

        $asset = Asset::find()->id($request->integer('assetId'))->siteId($request->integer('siteId'))->status(null)->one();

        abort_if(! $asset instanceof Asset, 404, 'Asset not found.');
        Gate::authorize('save', $asset);

        try {
            $changes = $analyzer->suggest($asset, [GenerateAltText::handle()]);
        } catch (Throwable $exception) {
            return $this->asJsonFailure(t('Couldn’t generate alt text: {error}', ['error' => $exception->getMessage()]));
        }

        $alt = $changes->siteAttributes[$asset->siteId]['alt'] ?? null;

        if (! is_string($alt)) {
            return $this->asJsonFailure(t('Couldn’t generate alt text for this image.'));
        }

        return $this->asJsonSuccess(t('Alt text generated. Review it before saving.'), ['value' => $alt]);
    }
}
