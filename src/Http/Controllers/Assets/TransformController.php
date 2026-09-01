<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Image\ImageTransformer;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

readonly class TransformController
{
    use EnforcesPermissions;
    use RespondsWithFlash;

    public function __construct(
        private ImageTransformer $imageTransformer,
        private AssetTransformers $assetTransformers,
        private CraftAssetTransformDriver $craftAssetTransformDriver,
    ) {}

    public function generate(Request $request): Response
    {
        $hasPrivateToken = false;
        $handle = '';
        $transformer = $this->imageTransformer;
        $transformIndexModel = null;
        $transformId = $request->integer('transformId');

        if (! $transformId && $request->filled('transformToken')) {
            try {
                $transformId = (int) Crypt::decryptString((string) $request->input('transformToken'));
                $hasPrivateToken = true;
            } catch (DecryptException) {
                abort(400, 'Invalid transform token.');
            }
        }

        if ($transformId) {
            $transformIndexModel = $transformer->getTransformIndexModelById($transformId);
            abort_if(! $transformIndexModel, 400, "Invalid transform ID: $transformId");
            $assetId = $transformIndexModel->assetId;
            try {
                $transform = $transformIndexModel->getTransform();
            } catch (Throwable $e) {
                abort(500, 'Image transform cannot be created.', ['exception' => $e]);
            }
        } else {
            $this->requirePermission('accessCp');
            $assetId = $request->input('assetId');
            $handle = $request->input('handle');
            abort_if(! $assetId, 400, 'Missing assetId');
            abort_if(! is_string($handle), 400, 'Invalid transform handle.');
        }

        $asset = Asset::findOne(['id' => $assetId]);

        abort_if(! $asset, 400, "Invalid asset ID: $assetId");

        if (
            isset($transformIndexModel) &&
            ! $transformer->transformHasUrlsForIndex($asset, $transformIndexModel)
        ) {
            if (! $hasPrivateToken) {
                $this->requirePermission('accessCp');
            }

            try {
                return $transformer->getTransformResponse($asset, $transformIndexModel);
            } catch (Throwable $e) {
                return $this->asBrokenImage($e);
            }
        }

        try {
            $url = isset($transformIndexModel)
                ? $transformer->getTransformUrlForIndex($asset, $transformIndexModel, true)
                : $this->craftAssetTransformDriver->withImmediateTransforms(
                    fn (): string => $this->assetTransformers->transform($asset, $handle)->url,
                );
        } catch (Throwable $e) {
            return $this->asBrokenImage($e);
        }

        if ($request->expectsJson()) {
            return new JsonResponse(['url' => $url]);
        }

        return redirect($url);
    }

    /**
     * Sends a broken image response based on a given exception.
     */
    private function asBrokenImage(?Throwable $e = null): Response
    {
        $statusCode = $e instanceof HttpException && $e->getStatusCode() ? $e->getStatusCode() : 500;

        return response()->file(Icons::resolveIconPath('image-slash'), [
            'Content-Type' => 'image/svg+xml',
        ])->setStatusCode($statusCode);
    }
}
