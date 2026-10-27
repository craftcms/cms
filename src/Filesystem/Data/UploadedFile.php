<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Data;

use CraftCms\Cms\Image\ImageHelper;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use finfo;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use League\Flysystem\MountManager;
use RuntimeException;
use Throwable;

/**
 * @since 6.0.0
 */
class UploadedFile
{
    private ?string $localPath = null;

    private ?string $localHash = null;

    public function __construct(
        public readonly FilesystemAdapter $disk,
        public readonly string $path,
        public readonly string $filename,
    ) {}

    public function size(): int
    {
        return $this->disk->size($this->path);
    }

    public function mimeType(): string
    {
        if ($this->disk instanceof AwsS3V3Adapter) {
            $client = $this->s3Client($this->disk);
            $result = $client->getObject([
                'Bucket' => $this->disk->getConfig()['bucket'],
                'Key' => $this->disk->path($this->path),
                'Range' => 'bytes=0-65535',
            ]);
            $sample = (string) $result['Body'];
        } else {
            $stream = $this->openStream();

            try {
                $sample = stream_get_contents($stream, 65536);
            } finally {
                fclose($stream);
            }
        }

        if ($sample === false) {
            throw new RuntimeException('Unable to inspect the uploaded file.');
        }

        return new finfo(FILEINFO_MIME_TYPE)->buffer($sample) ?: 'application/octet-stream';
    }

    public function localPath(): string
    {
        if ($this->localPath !== null) {
            return $this->localPath;
        }

        $path = Path::temp(File::uniqueName($this->filename));
        $target = fopen($path, 'wb');

        if ($target === false) {
            throw new RuntimeException('Unable to create a temporary upload file.');
        }

        $this->localPath = $path;

        try {
            $source = $this->openStream();

            try {
                if (stream_copy_to_stream($source, $target) !== $this->size()) {
                    throw new RuntimeException('Unable to read the complete uploaded file.');
                }
            } finally {
                fclose($source);
            }
        } finally {
            fclose($target);
        }

        $this->localHash = hash_file('sha256', $path) ?: throw new RuntimeException('Unable to checksum the uploaded file.');

        return $path;
    }

    public function storeAs(FilesystemAdapter $destination, string $path, string $mimeType, ?string $processedPath = null): void
    {
        if ($processedPath === null || (
            $processedPath === $this->localPath &&
            $this->localHash === hash_file('sha256', $processedPath)
        )) {
            if ($this->copyWithinS3($destination, $path, $mimeType)) {
                return;
            }

            new MountManager([
                'source' => $this->disk->getDriver(),
                'destination' => $destination->getDriver(),
            ])->copy("source://{$this->path}", "destination://$path", [
                'mimetype' => $mimeType,
                'visibility' => $destination->getConfig()['visibility'] ?? 'private',
                'MetadataDirective' => 'REPLACE',
            ]);

            return;
        }

        $stream = fopen($processedPath, 'rb');

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to open the uploaded file.');
        }

        try {
            if (! $destination->writeStream($path, $stream, ['mimetype' => $mimeType])) {
                throw new RuntimeException('Unable to store the uploaded file.');
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * Copies the file to another S3 disk without downloading it, if the disks share credentials and an endpoint, such
     * as a bucket's upload staging prefix and its assets. Objects over 5 GiB are copied in parts.
     *
     * @return bool Whether the file was copied.
     */
    private function copyWithinS3(FilesystemAdapter $destination, string $path, string $mimeType): bool
    {
        if (! $this->disk instanceof AwsS3V3Adapter || ! $destination instanceof AwsS3V3Adapter) {
            return false;
        }

        $sourceConfig = $this->disk->getConfig();
        $destinationConfig = $destination->getConfig();
        $account = static fn (array $config): array => [
            $config['key'] ?? null,
            $config['secret'] ?? null,
            $config['token'] ?? null,
            $config['endpoint'] ?? null,
        ];

        if ($account($sourceConfig) !== $account($destinationConfig)) {
            return false;
        }

        $this->s3Client($destination)->copy(
            $sourceConfig['bucket'],
            $this->disk->path($this->path),
            $destinationConfig['bucket'],
            $destination->path($path),
            ($destinationConfig['visibility'] ?? 'private') === 'public' ? 'public-read' : 'private',
            [
                'params' => [...($destinationConfig['options'] ?? []), 'ContentType' => $mimeType],
                // Takes the destination's metadata rather than the staged object's, for single and multipart copies
                'MetadataDirective' => 'REPLACE',
                'metadata_directive' => 'REPLACE',
            ],
        );

        return true;
    }

    /**
     * Returns whether the file is staged on the local filesystem, so reading it all is no more costly than reading
     * part of it.
     */
    public function isLocal(): bool
    {
        return $this->localPath !== null || $this->disk instanceof LocalFilesystemAdapter;
    }

    /**
     * Returns the image’s width and height, read from the start of the file, or `null` if they can’t be read that way.
     *
     * @return array{int, int}|null
     */
    public function imageSize(): ?array
    {
        try {
            $stream = $this->openStream();

            try {
                $size = ImageHelper::imageSizeByStream($stream);
            } finally {
                fclose($stream);
            }
        } catch (Throwable) {
            return null;
        }

        return is_int($size[0] ?? null) && is_int($size[1] ?? null) ? [$size[0], $size[1]] : null;
    }

    public function release(): void
    {
        if ($this->localPath !== null) {
            File::delete($this->localPath);
            $this->localPath = null;
            $this->localHash = null;
        }
    }

    /** @return resource */
    private function openStream(): mixed
    {
        $stream = $this->disk->readStream($this->path);

        if (! is_resource($stream)) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        return $stream;
    }

    /** The SDK is an optional dependency supplied by the S3 filesystem adapter. */
    private function s3Client(AwsS3V3Adapter $disk): mixed
    {
        return $disk->getClient();
    }
}
