<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Uploaders;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadPartRequest;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Support\PHP;
use RuntimeException;

class ChunkedUploader implements Uploader
{
    public function __construct(private readonly Filesystems $filesystems) {}

    public function start(UploadSession $session): void
    {
        $requestLimit = PHP::sizeToBytes(ini_get('post_max_size'));
        $session->chunkSize = min(
            Cms::config()->uploadChunkSize,
            $requestLimit > 0 ? $requestLimit : PHP_INT_MAX,
        );

        if ($session->chunkSize < 1) {
            throw new RuntimeException('uploadChunkSize must be greater than zero.');
        }
    }

    public function partRequest(UploadSession $session, int $part): UploadPartRequest
    {
        $routeName = request()->isCpRequest()
            ? 'craft.actions.craft.cp.uploads.chunk'
            : 'craft.actions.craft.uploads.chunk';

        return new UploadPartRequest(
            url: route($routeName, ['upload' => $session->id, 'part' => $part]),
            method: 'POST',
            headers: ['X-CSRF-TOKEN' => csrf_token(), 'Content-Type' => 'application/octet-stream'],
        );
    }

    /** @param resource $stream */
    public function receive(UploadSession $session, int $part, mixed $stream): void
    {
        $disk = $this->filesystems->disk($session->disk);
        $expected = $session->partSize($part);
        $buffer = tmpfile();

        if ($buffer === false) {
            throw new RuntimeException('Unable to create a temporary upload file.');
        }

        try {
            $size = stream_copy_to_stream($stream, $buffer, $expected + 1);
            abort_unless($size === $expected, 422, 'The upload part has an incorrect size.');
            rewind($buffer);

            if (! $disk->writeStream($this->partPath($session, $part), $buffer, ['visibility' => 'private'])) {
                throw new RuntimeException('Unable to store the upload part.');
            }
        } finally {
            fclose($buffer);
        }
    }

    public function complete(UploadSession $session): UploadedFile
    {
        $disk = $this->filesystems->disk($session->disk);

        if ($disk->exists($session->path()) && $disk->size($session->path()) === $session->size) {
            return new UploadedFile($disk, $session->path(), $session->filename);
        }

        $buffer = tmpfile();

        if ($buffer === false) {
            throw new RuntimeException('Unable to assemble the uploaded file.');
        }

        try {
            for ($part = 1; $part <= $session->partCount(); $part++) {
                $path = $this->partPath($session, $part);
                abort_unless($disk->exists($path), 422, 'The upload is missing a part.');
                $stream = $disk->readStream($path);

                if (! is_resource($stream)) {
                    throw new RuntimeException('Unable to read an upload part.');
                }

                try {
                    $size = stream_copy_to_stream($stream, $buffer, $session->partSize($part) + 1);
                    abort_unless($size === $session->partSize($part), 422, 'The upload part has an incorrect size.');
                } finally {
                    fclose($stream);
                }
            }

            rewind($buffer);

            if (! $disk->writeStream($session->path(), $buffer, ['visibility' => 'private'])) {
                throw new RuntimeException('Unable to store the assembled upload.');
            }
        } finally {
            fclose($buffer);
        }

        return new UploadedFile($disk, $session->path(), $session->filename);
    }

    public function abort(UploadSession $session): void
    {
        if (! $this->filesystems->disk($session->disk)->deleteDirectory($session->prefix())) {
            throw new RuntimeException('Unable to remove the temporary upload.');
        }
    }

    private function partPath(UploadSession $session, int $part): string
    {
        return $session->prefix()."/parts/$part";
    }
}
