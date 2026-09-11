<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Uploaders;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Filesystem\Data\UploadSetup;
use CraftCms\Cms\Filesystem\Filesystems;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Support\PHP;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TusUploader implements Uploader
{
    public function __construct(private readonly Filesystems $filesystems) {}

    public function start(UploadSession $session): UploadSetup
    {
        $requestLimit = PHP::sizeToBytes(ini_get('post_max_size'));
        $chunkSize = min(
            Cms::config()->uploadChunkSize,
            $requestLimit > 0 ? $requestLimit : PHP_INT_MAX,
        );

        if ($chunkSize < 1) {
            throw new RuntimeException('uploadChunkSize must be greater than zero.');
        }

        $routeName = request()->isCpRequest()
            ? 'craft.actions.craft.cp.uploads.transfer'
            : 'craft.actions.craft.uploads.transfer';

        return new UploadSetup(
            chunkSize: $chunkSize,
            state: ['offset' => 0, 'parts' => []],
            transport: ['type' => 'tus', 'options' => ['url' => route($routeName, ['upload' => $session->id])]],
        );
    }

    public function handleRequest(Request $request, UploadSession $session): Response
    {
        try {
            $response = $this->respond($request, $session);
        } catch (HttpException $exception) {
            $exception->setHeaders([...$exception->getHeaders(), 'Tus-Resumable' => '1.0.0']);

            throw $exception;
        }

        $response->headers->set('Tus-Resumable', '1.0.0');

        return $response;
    }

    private function respond(Request $request, UploadSession $session): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->noContent(headers: [
                'Tus-Version' => '1.0.0',
                'Tus-Extension' => 'termination,expiration',
                'Tus-Max-Size' => (string) Cms::config()->maxUploadFileSize,
            ]);
        }

        abort_unless($request->header('Tus-Resumable') === '1.0.0', 412, headers: ['Tus-Version' => '1.0.0']);
        abort_unless(in_array($request->method(), ['HEAD', 'PATCH', 'DELETE'], true), 405);

        if ($request->isMethod('DELETE')) {
            $this->abort($session);
            $session->delete();

            return response()->noContent();
        }

        if ($request->isMethod('PATCH')) {
            abort_unless($request->header('Content-Type') === 'application/offset+octet-stream', 415);
            $offset = filter_var($request->header('Upload-Offset'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            abort_if($offset === false, 400, 'A valid Upload-Offset header is required.');
            abort_if($session->result !== null, 409, 'This upload has already completed.');
            $stream = $request->getContent(asResource: true);

            try {
                $this->receive($session, $offset, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return response()->noContent($request->isMethod('HEAD') ? 200 : 204, [
            'Upload-Offset' => (string) $this->offset($session),
            'Upload-Length' => (string) $session->size,
            'Upload-Expires' => $session->expiresAt->toRfc7231String(),
            'Cache-Control' => 'no-store',
        ]);
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
