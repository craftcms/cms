<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use finfo;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\MountManager;
use RuntimeException;

class UploadedAssetFile
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
            throw new RuntimeException('Unable to create a temporary asset file.');
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
                throw new RuntimeException('Unable to store the uploaded asset.');
            }
        } finally {
            fclose($stream);
        }
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
