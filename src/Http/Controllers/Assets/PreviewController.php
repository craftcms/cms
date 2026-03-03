<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use craft\assetpreviews\Image as ImagePreview;
use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\Concerns\EnforcesVolumePermissions;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use yii\base\NotSupportedException;

use function CraftCms\Cms\t;

final readonly class PreviewController
{
    use EnforcesVolumePermissions;
    use RespondsWithFlash;

    public function __construct(
        private Assets $assets,
        private HtmlStack $htmlStack,
    ) {}

    public function previewThumb(Request $request): JsonResponse
    {
        $request->validate([
            'assetId' => ['required', 'integer'],
            'width' => ['required', 'integer'],
            'height' => ['required', 'integer'],
        ]);

        $asset = Asset::findOne($request->integer('assetId'));

        abort_if(! $asset, 400, 'Invalid asset ID: '.$request->integer('assetId'));

        $this->requireVolumePermissionByAsset('editImages', $asset);
        $this->requirePeerVolumePermissionByAsset('editPeerImages', $asset);

        return new JsonResponse([
            'img' => $asset->getPreviewThumbImg($request->integer('width'), $request->integer('height')),
        ]);
    }

    public function previewFile(Request $request): Response
    {
        $request->validate([
            'assetId' => ['required'],
            'requestId' => ['required'],
        ]);

        $assetId = $request->input('assetId');
        $requestId = $request->input('requestId');

        /** @var Asset|null $asset */
        $asset = Asset::find()->id($assetId)->one();

        if (! $asset) {
            return $this->asFailure(t('Asset not found with that id'));
        }

        $this->requireVolumePermissionByAsset('viewAssets', $asset);
        $this->requirePeerVolumePermissionByAsset('viewPeerAssets', $asset);

        $previewHtml = null;
        $previewHandler = $this->assets->getAssetPreviewHandler($asset);
        $variables = [];

        if (($previewHandler instanceof ImagePreview) && $asset->id !== $request->user()->photoId) {
            $variables['editFocal'] = true;

            try {
                $this->requireVolumePermissionByAsset('editImages', $asset);
                $this->requirePeerVolumePermissionByAsset('editPeerImages', $asset);
            } catch (HttpException) {
                $variables['editFocal'] = false;
            }
        }

        if ($previewHandler) {
            try {
                $previewHtml = $previewHandler->getPreviewHtml($variables);
            } catch (NotSupportedException) {
                // No big deal
            }
        }

        return $this->asSuccess(data: [
            'previewHtml' => $previewHtml,
            'headHtml' => $this->htmlStack->headHtml(),
            'bodyHtml' => $this->htmlStack->bodyHtml(),
            'requestId' => $requestId,
        ]);
    }
}
