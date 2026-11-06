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
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

/**
 * @since 6.0.0
 */
readonly class AssetUploads implements UploadHandler
{
    private const int MaxRedirects = 3;

    private const array FileSources = ['download_url', 'data', 'path'];

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
     * Returns the filename for a file reference: the `filename` override, then `file_name`, then the basename of its
     * download URL or path.
     *
     * @param  array<string, mixed>  $file
     */
    public function filename(array $file, ?string $filename): string
    {
        $source = $this->source($file);
        $filename ??= $file['file_name'] ?? match ($source) {
            'download_url' => rawurldecode(basename((string) parse_url($file['download_url'], PHP_URL_PATH))),
            'path' => basename($file['path']),
            default => null,
        };

        if (! is_string($filename) || trim($filename) === '') {
            throw new RuntimeException('Provide filename when the file reference does not include file_name.');
        }

        return $filename;
    }

    /**
     * Turns a client file reference into a bounded, temporary file.
     *
     * A file reference provides exactly one source: `download_url` (a public HTTPS URL), `data` (base64-encoded
     * contents), or `path` (a local file, for the stdio server only).
     *
     * @template T
     *
     * @param  array<string, mixed>  $file
     * @param  Closure(UploadedFile): T  $callback
     * @return T
     */
    public function consumeFile(array $file, string $filename, Closure $callback): mixed
    {
        $source = $this->source($file);

        if ($source === 'download_url' && strtolower((string) parse_url($file['download_url'], PHP_URL_SCHEME)) !== 'https') {
            throw new RuntimeException('File references must use an HTTPS download URL.');
        }

        $disk = Storage::build(['driver' => 'local', 'root' => Path::temp()]);

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('File references require a local filesystem adapter for temporary storage.');
        }

        $path = File::uniqueName('mcp-upload');
        $upload = new UploadedFile($disk, $path, $filename);
        $maxSize = AssetsHelper::getMaxAssetUploadSize();

        try {
            if ($source === 'download_url') {
                $this->downloads->download($file['download_url'], $disk->path($path), $maxSize, self::MaxRedirects);
            } elseif ($source === 'data') {
                $this->decode($file['data'], $disk->path($path), $maxSize);
            } else {
                $this->copyLocal($file['path'], $disk->path($path), $maxSize);
            }

            return $callback($upload);
        } finally {
            try {
                $upload->release();
            } finally {
                $disk->delete($path);
            }
        }
    }

    /** @param array<string, mixed> $file */
    private function source(array $file): string
    {
        $sources = array_keys(array_filter(
            Arr::only($file, self::FileSources),
            static fn (mixed $value): bool => $value !== null,
        ));

        if (count($sources) !== 1 || ! is_string($file[$sources[0]]) || $file[$sources[0]] === '') {
            throw new RuntimeException(sprintf('Provide a file reference with exactly one of: %s.', implode(', ', self::FileSources)));
        }

        return $sources[0];
    }

    private function decode(string $data, string $path, int $maxSize): void
    {
        // Allow data URLs, e.g. `data:image/png;base64,...`
        if (str_starts_with($data, 'data:') && ($comma = strpos($data, ',')) !== false) {
            $data = substr($data, $comma + 1);
        }

        if (strlen($data) > (int) ceil($maxSize / 3) * 4 + 4) {
            throw new RuntimeException('The file exceeds the maximum upload size.');
        }

        $contents = base64_decode((string) preg_replace('/\s+/', '', $data), true);

        if ($contents === false || $contents === '') {
            throw new RuntimeException('The data must be base64-encoded file contents.');
        }

        if (strlen($contents) > $maxSize) {
            throw new RuntimeException('The file exceeds the maximum upload size.');
        }

        File::put($path, $contents);
    }

    private function copyLocal(string $source, string $path, int $maxSize): void
    {
        // Over HTTP, a path would refer to the web server’s filesystem, not the client’s
        if (! Context::getHidden(StdioTransport::ContextStdio)) {
            throw new RuntimeException('Local paths are only supported by the stdio server. Use assets_upload_prepare to upload a local file over HTTP.');
        }

        $isAbsolute = str_starts_with($source, '/') || preg_match('#^[a-z]:[\\\\/]#i', $source);
        $real = $isAbsolute ? realpath($source) : false;

        if ($real === false || ! is_file($real) || ! is_readable($real)) {
            throw new RuntimeException('The path must be an absolute path to a readable file.');
        }

        // Only allow files where a local volume could live: within the project, outside system directories
        $root = realpath(base_path());

        if ($root === false || ! str_starts_with($real, $root.DIRECTORY_SEPARATOR) || Security::isRestrictedDir(dirname($real))) {
            throw new RuntimeException('The path must be within the Craft project, outside of system directories and the project root itself.');
        }

        if (filesize($real) > $maxSize) {
            throw new RuntimeException('The file exceeds the maximum upload size.');
        }

        // Copy with a bound, since the source may grow or misreport its size
        $in = fopen($real, 'rb');
        $out = fopen($path, 'wb');

        try {
            if ($in === false || $out === false || stream_copy_to_stream($in, $out, $maxSize + 1) === false) {
                throw new RuntimeException('The file could not be read.');
            }
        } finally {
            is_resource($in) && fclose($in);
            is_resource($out) && fclose($out);
        }

        clearstatcache(true, $path);

        if (filesize($path) > $maxSize) {
            throw new RuntimeException('The file exceeds the maximum upload size.');
        }
    }

    /** @param array<string, mixed> $parameters */
    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse
    {
        throw new ConflictHttpException('Complete this upload with the assets_create or assets_replace MCP tool.');
    }
}
