<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use Closure;
use CraftCms\Cms\Asset\AssetUploads as BaseAssetUploads;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSessionData;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Filesystem\Uploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function consume(Request $request, string $id, Closure $callback): mixed
    {
        return $this->uploads->withSession($request, $id, function (UploadSession $session, Uploader $uploader) use ($callback) {
            abort_unless($session->handler === self::class, 404);
            abort_if($session->result !== null, 409, 'This upload has already completed.');

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

    /** @param array<string, mixed> $parameters */
    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse
    {
        throw new ConflictHttpException('Complete this upload with the assets.create MCP tool.');
    }
}
