<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Uploaders;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\ReceivesTusUploads;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Support\PHP;
use RuntimeException;

class TusUploader implements ReceivesTusUploads
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

        $session->state = ['offset' => 0, 'parts' => []];
    }

    public function clientConfig(UploadSession $session): array
    {
        $routeName = request()->isCpRequest()
            ? 'craft.actions.craft.cp.uploads.tus'
            : 'craft.actions.craft.uploads.tus';

        return ['type' => 'tus', 'options' => ['url' => route($routeName, ['upload' => $session->id])]];
    }

    public function offset(UploadSession $session): int
    {
        return $session->state['offset'];
    }

    public function uploaded(UploadSession $session): bool
    {
        return $session->state['offset'] === $session->size;
    }

    /** @param resource $stream */
    public function receive(UploadSession $session, int $offset, mixed $stream): void
    {
        abort_unless($offset === $session->state['offset'], 409, 'The upload offset does not match.', [
            'Upload-Offset' => (string) $session->state['offset'],
        ]);

        $disk = $this->filesystems->disk($session->disk);
        $limit = min($session->chunkSize, $session->size - $offset);
        $buffer = tmpfile();

        if ($buffer === false) {
            throw new RuntimeException('Unable to create a temporary upload file.');
        }

        try {
            $size = stream_copy_to_stream($stream, $buffer, $limit + 1);

            if ($size === false) {
                throw new RuntimeException('Unable to read the upload request.');
            }

            abort_if($size > $limit, 413, 'The upload exceeds the remaining file size or chunk size limit.');

            if ($size === 0) {
                return;
            }

            rewind($buffer);

            if (! $disk->writeStream($this->partPath($session, $offset), $buffer, ['visibility' => 'private'])) {
                throw new RuntimeException('Unable to store the upload part.');
            }

            $session->state = [
                ...$session->state,
                'offset' => $offset + $size,
                'parts' => [...$session->state['parts'], ['offset' => $offset, 'size' => $size]],
            ];
        } finally {
            fclose($buffer);
        }
    }

    public function complete(UploadSession $session): UploadedFile
    {
        abort_unless($session->state['offset'] === $session->size, 422, 'The upload is incomplete.');

        $disk = $this->filesystems->disk($session->disk);

        if ($disk->exists($session->path()) && $disk->size($session->path()) === $session->size) {
            return new UploadedFile($disk, $session->path(), $session->filename);
        }

        $buffer = tmpfile();

        if ($buffer === false) {
            throw new RuntimeException('Unable to assemble the uploaded file.');
        }

        try {
            foreach ($session->state['parts'] as $part) {
                $path = $this->partPath($session, $part['offset']);
                abort_unless($disk->exists($path), 422, 'The upload is missing a part.');
                $stream = $disk->readStream($path);

                if (! is_resource($stream)) {
                    throw new RuntimeException('Unable to read an upload part.');
                }

                try {
                    $size = stream_copy_to_stream($stream, $buffer, $part['size'] + 1);
                    abort_unless($size === $part['size'], 422, 'The upload part has an incorrect size.');
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

    private function partPath(UploadSession $session, int $offset): string
    {
        return $session->prefix()."/parts/$offset";
    }
}
