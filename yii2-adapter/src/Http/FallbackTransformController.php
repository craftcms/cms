<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Http;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Filesystem\join_paths;

class FallbackTransformController
{
    public function __invoke(Request $request): Response
    {
        try {
            $transform = Crypt::decrypt($request->input('transform'));
        } catch (DecryptException) {
            abort(400, 'Request contained an invalid transform param.');
        }

        [$assetId, $transformString] = explode(',', (string) $transform, 2);

        /** @var Asset|null $asset */
        $asset = Asset::find()->id($assetId)->one();
        abort_if(!$asset, 400, "Invalid asset ID: $assetId");

        $useOriginal = $transformString === 'original';

        if ($useOriginal && ($sourceDisk = $asset->getVolume()->sourceDisk()) instanceof LocalFilesystemAdapter) {
            return response()->file($sourceDisk->path($asset->getPath()), [
                'Content-Disposition' => 'inline; filename="' . $asset->getFilename() . '"',
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        if ($useOriginal) {
            $extension = $asset->getExtension();
        } else {
            $transform = new ImageTransform(ImageTransformHelper::parseTransformString($transformString));
            $extension = $transform->format ?: ImageTransformHelper::detectTransformFormat($asset);
        }

        $filename = sprintf('%s.%s', $asset->id, $extension);
        $path = Path::imageTransforms(join_paths($transformString, $filename));

        if (!file_exists($path) || filemtime($path) < ($asset->dateModified?->getTimestamp() ?? 0)) {
            $tempPath = $useOriginal
                ? $asset->getCopyOfFile()
                : ImageTransformHelper::generateTransform($asset, $transform);

            File::ensureDirectoryExists(dirname($path));
            rename($tempPath, $path);
        }

        $responseFilename = sprintf('%s.%s', $asset->getFilename(false), $extension);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $responseFilename . '"',
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
