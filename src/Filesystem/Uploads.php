<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\ReceivesTusUploads;
use CraftCms\Cms\Filesystem\Contracts\SignsS3Uploads;
use CraftCms\Cms\Filesystem\Contracts\Uploader;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Singleton]
class Uploads
{
    public function __construct(private readonly Uploaders $uploaders) {}

    /**
     * @param  class-string<UploadHandler>  $handler
     * @param  array<string, mixed>  $parameters
     */
    public function start(Request $request, string $handler, string $filename, int $size, array $parameters): UploadSession
    {
        $parameters = app($handler)->authorize($request, $parameters, $filename, $size);

        if (Cms::config()->uploadSessionDuration < 1) {
            throw new RuntimeException('uploadSessionDuration must be greater than zero.');
        }

        $diskReference = Cms::config()->getTempAssetUploadFs();
        $name = $this->uploaders->getDefaultDriver();
        $uploader = $this->uploaders->driver($name);

        $session = UploadSession::create([
            'id' => (string) Str::uuid(),
            'owner' => $this->owner($request),
            'handler' => $handler,
            'uploader' => $name,
            'disk' => $diskReference,
            'filename' => $filename,
            'size' => $size,
            'parameters' => $parameters,
            'state' => [],
            'expiresAt' => now()->addSeconds(Cms::config()->uploadSessionDuration),
        ]);

        $uploader->start($session);
        abort_unless($session->chunkSize > 0, 500, 'The uploader returned an invalid chunk size.');
        $session->save();

        return $session;
    }

    /** @return array{url: string} */
    public function sign(Request $request, string $id, string $method, string $key, string $uploadId, ?int $part): array
    {
        return $this->withSession($request, $id, function (UploadSession $session, Uploader $uploader) use ($method, $key, $uploadId, $part) {
            abort_unless($uploader instanceof SignsS3Uploads, 409, 'This uploader does not use S3.');
            abort_if($session->result !== null, 409, 'This upload has already completed.');
            $config = $uploader->clientConfig($session)['options'];
            abort_unless($key === $config['key'] && $uploadId === $config['uploadId'], 404);

            return $uploader->sign($session, $method, $part);
        }, authorize: $method !== 'DELETE', allowExpired: $method === 'DELETE');
    }

    /** @return array{uploaded: bool} */
    public function status(Request $request, string $id): array
    {
        return $this->withSession($request, $id, fn (UploadSession $session, Uploader $uploader) => [
            'uploaded' => $session->result !== null || $uploader->uploaded($session),
        ]);
    }

    /** @return array{session: UploadSession, offset: int} */
    public function tus(Request $request, string $id, ?int $offset = null): array
    {
        return $this->withSession($request, $id, function (UploadSession $session, Uploader $uploader) use ($request, $offset) {
            abort_unless($uploader instanceof ReceivesTusUploads, 409, 'This uploader does not receive bytes through tus.');

            if ($offset !== null) {
                abort_if($session->result !== null, 409, 'This upload has already completed.');
                $stream = $request->getContent(asResource: true);

                try {
                    $uploader->receive($session, $offset, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }

            return ['session' => $session, 'offset' => $uploader->offset($session)];
        });
    }

    public function complete(Request $request, string $id): JsonResponse
    {
        $result = $this->withSession($request, $id, function (UploadSession $session, Uploader $uploader) use ($request) {
            if ($session->result !== null) {
                return new JsonResponse($session->result['data'], $session->result['status']);
            }

            $file = $uploader->complete($session);

            try {
                abort_unless($file->size() === $session->size, 422, 'The uploaded file has an incorrect size.');
                $result = $this->handler($session)->complete($request, $session->parameters, $file);
                $session->result = ['data' => $result->getData(true), 'status' => $result->getStatusCode()];

                return $result;
            } finally {
                $file->release();
            }
        });

        try {
            $this->withSession($request, $id, function (UploadSession $session, Uploader $uploader) {
                $this->clean($session, $uploader);
            }, authorize: false);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $result;
    }

    public function cancel(Request $request, string $id): void
    {
        try {
            $this->withSession($request, $id, function (UploadSession $session, Uploader $uploader) {
                $this->clean($session, $uploader);
                $session->delete();
            }, authorize: false, allowExpired: true);
        } catch (ModelNotFoundException $exception) {
            if ($exception->getModel() !== UploadSession::class) {
                throw $exception;
            }
        }
    }

    /** @return array{removed: int, failed: int} */
    public function cleanupExpired(): array
    {
        $removed = $failed = 0;

        foreach (UploadSession::query()->where('expiresAt', '<=', now())->lazyById(100) as $expired) {
            try {
                $removed += DB::transaction(function () use ($expired) {
                    $session = UploadSession::query()->lockForUpdate()->find($expired->id);

                    if ($session === null || $session->expiresAt->isFuture()) {
                        return 0;
                    }

                    $this->clean($session, $this->uploader($session));
                    $session->delete();

                    return 1;
                });
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return ['removed' => $removed, 'failed' => $failed];
    }

    /**
     * @template T
     *
     * @param  Closure(UploadSession, Uploader): T  $callback
     * @return T
     */
    private function withSession(Request $request, string $id, Closure $callback, bool $authorize = true, bool $allowExpired = false): mixed
    {
        return DB::transaction(function () use ($request, $id, $callback, $authorize, $allowExpired) {
            $session = UploadSession::query()->lockForUpdate()->findOrFail($id);
            abort_unless(hash_equals($session->owner, $this->owner($request)), 404);
            abort_if(! $allowExpired && $session->expiresAt->isPast(), 410, 'The upload session has expired.');

            if ($authorize) {
                $parameters = $this->handler($session)->authorize($request, $session->parameters, $session->filename, $session->size);
                abort_unless(Arr::sortRecursive($parameters) === Arr::sortRecursive($session->parameters), 403, 'The upload destination is no longer authorized.');
            }

            $result = $callback($session, $this->uploader($session));

            if ($session->exists) {
                $session->expiresAt = now()->addSeconds(Cms::config()->uploadSessionDuration);
                $session->save();
            }

            return $result;
        });
    }

    private function owner(Request $request): string
    {
        return $request->user()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'guest:'.hash('sha256', $request->session()->getId());
    }

    private function handler(UploadSession $session): UploadHandler
    {
        return app($session->handler);
    }

    private function uploader(UploadSession $session): Uploader
    {
        return $this->uploaders->driver($session->uploader);
    }

    private function clean(UploadSession $session, Uploader $uploader): void
    {
        if (! ($session->state['cleaned'] ?? false)) {
            $uploader->abort($session);
            $session->state = [...$session->state, 'cleaned' => true];
        }
    }
}
