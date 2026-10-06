<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Closure;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\AssetUploads as BaseAssetUploads;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\RemoteFileDownloader;
use CraftCms\Cms\Filesystem\Uploads;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

/**
 * @since 6.0.0
 */
readonly class AssetUploads implements UploadHandler
{
    public function __construct(
        private BaseAssetUploads $assets,
        private Uploads $uploads,
        private RemoteFileDownloader $downloads,
    ) {}

    /** @param array<string, mixed> $parameters */
    public function start(Request $request, string $filename, int $size, array $parameters): UploadSessionData
    {
        return $this->uploads->start($request, self::class, $filename, $size, $parameters);
    }

    /**
     * @template T
     *
     * @param  Closure(UploadedFile, array<string, mixed>): T  $callback
     * @return T
     */
    public function consume(Request $request, string $id, Closure $callback, string $operation = 'upload', ?int $assetId = null): mixed
    {
        return $this->uploads->withSession($request, $id, function (UploadSession $session, Uploader $uploader) use ($operation, $callback, $assetId) {
            abort_unless($session->handler === self::class, 404);
            abort_if($session->result !== null, 409, 'This upload has already completed.');
            abort_unless(($session->parameters['operation'] ?? null) === $operation, 422, 'The upload operation does not match this tool.');
            abort_if($assetId !== null && ($session->parameters['assetId'] ?? null) !== $assetId, 422, 'The upload was prepared for a different asset.');

            $file = $uploader->complete($session);

            try {
                abort_unless($file->size() === $session->size, 422, 'The uploaded file has an incorrect size.');

                return $callback($file, $session->parameters);
            } finally {
                try {
                    $file->release();
                } catch (Throwable $exception) {
                    report($exception);
                }

                try {
                    $uploader->abort($session);
                } catch (Throwable $exception) {
                    report($exception);
                }

                $session->delete();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function authorize(Request $request, array $parameters, string $filename, int $size): array
    {
        return $this->assets->authorize($request, $parameters, $filename, $size);
    }

    /**
     * @template T
     *
     * @param  Closure(UploadedFile): T  $callback
     * @return T
     */
    public function consumeFile(string $url, string $filename, Closure $callback): mixed
    {
        if (strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new RuntimeException('File references must use an HTTPS download URL.');
        }

        $disk = Storage::build(['driver' => 'local', 'root' => Path::temp()]);

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('File references require a local filesystem adapter for temporary storage.');
        }

        $path = File::uniqueName('mcp-upload');
        $source = new UploadedFile($disk, $path, $filename);

        try {
            $this->downloads->download($url, $disk->path($path), AssetsHelper::getMaxAssetUploadSize());

            return $callback($source);
        } finally {
            try {
                $source->release();
            } finally {
                $disk->delete($path);
            }
        }
    }

    /** @param array<string, mixed> $parameters */
    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse
    {
        throw new ConflictHttpException('Complete this upload with the assets.create or assets.replace MCP tool.');
    }
}
