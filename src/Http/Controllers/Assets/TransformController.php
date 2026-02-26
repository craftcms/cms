<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Assets;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransformHelper;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final readonly class TransformController
{
    use RespondsWithFlash;

    public function generate(Request $request): Response
    {
        if ($transformId = $request->integer('transformId')) {
            $transformer = new ImageTransformer;
            $transformIndexModel = $transformer->getTransformIndexModelById($transformId);
            abort_if(! $transformIndexModel, 400, "Invalid transform ID: $transformId");
            $assetId = $transformIndexModel->assetId;
            try {
                $transform = $transformIndexModel->getTransform();
            } catch (Throwable $e) {
                abort(500, 'Image transform cannot be created.', ['exception' => $e]);
            }
        } else {
            $assetId = $request->input('assetId');
            $handle = $request->input('handle');
            abort_if(! $assetId, 400, 'Missing assetId');
            abort_if(! is_string($handle), 400, 'Invalid transform handle.');
            try {
                $transform = ImageTransformHelper::normalizeTransform($handle);
            } catch (Throwable $e) {
                abort(500, 'Image transform cannot be created.', ['exception' => $e]);
            }
            abort_if(! $transform, 400, "Invalid transform handle: $handle");
            $transformer = $transform->getImageTransformer();
        }

        $asset = Asset::findOne(['id' => $assetId]);

        abort_if(! $asset, 400, "Invalid asset ID: $assetId");

        try {
            $url = $transformer->getTransformUrl($asset, $transform, true);
        } catch (Throwable $e) {
            return $this->asBrokenImage($e);
        }

        if ($request->expectsJson()) {
            return new JsonResponse(['url' => $url]);
        }

        return redirect($url);
    }

    public function generateFallback(Request $request): Response
    {
        try {
            $transform = Crypt::decrypt($request->input('transform'));
        } catch (DecryptException) {
            abort(400, 'Request contained an invalid transform param.');
        }

        [$assetId, $transformString] = explode(',', (string) $transform, 2);

        /** @var Asset|null $asset */
        $asset = Asset::find()->id($assetId)->one();
        abort_if(! $asset, 400, "Invalid asset ID: $assetId");

        // If we're returning the original asset, and it's in a local FS, just read the file out directly
        $useOriginal = $transformString === 'original';
        if ($useOriginal) {
            $volume = $asset->getVolume();
            if ($volume->sourceDisk() instanceof LocalFilesystemAdapter) {
                $path = sprintf(
                    '%s/%s/%s',
                    rtrim($volume->sourceDisk()->path(''), '/'),
                    rtrim($volume->getSubpath(), '/'),
                    $asset->getPath()
                );

                return response()->file($path, [
                    'Content-Disposition' => 'inline; filename="'.$asset->getFilename().'"',
                    'Cache-Control' => 'public, max-age=31536000',
                ]);
            }
        }

        if ($useOriginal) {
            $ext = $asset->getExtension();
        } else {
            $transform = new ImageTransform(ImageTransformHelper::parseTransformString($transformString));
            $ext = $transform->format ?: ImageTransformHelper::detectTransformFormat($asset);
        }

        $filename = sprintf('%s.%s', $asset->id, $ext);
        $path = implode(DIRECTORY_SEPARATOR, [
            Craft::$app->getPath()->getImageTransformsPath(),
            $transformString,
            $filename,
        ]);

        if (! file_exists($path) || filemtime($path) < ($asset->dateModified?->getTimestamp() ?? 0)) {
            if ($useOriginal) {
                $tempPath = $asset->getCopyOfFile();
            } else {
                $tempPath = ImageTransformHelper::generateTransform($asset, $transform);
            }

            FileHelper::createDirectory(dirname($path));
            rename($tempPath, $path);
        }

        $responseFilename = sprintf('%s.%s', $asset->getFilename(false), $ext);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.$responseFilename.'"',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }

    /**
     * Sends a broken image response based on a given exception.
     */
    private function asBrokenImage(?Throwable $e = null): Response
    {
        $statusCode = $e instanceof HttpException && $e->getStatusCode() ? $e->getStatusCode() : 500;

        return response()->file(Aliases::get('@appicons/broken-image.svg'), [
            'Content-Type' => 'image/svg+xml',
        ])->setStatusCode($statusCode);
    }
}
