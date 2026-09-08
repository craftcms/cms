<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Closure;
use CraftCms\Cms\Asset\Contracts\AssetUploader;
use CraftCms\Cms\Asset\Data\UploadResult;
use CraftCms\Cms\Asset\Models\UploadSession;
use CraftCms\Cms\Asset\Uploaders\ChunkedUploader;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Requests\UploadRequest;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

#[Singleton]
class AssetUploads
{
    /** @var Closure(Request, array{folderId: int|null, filename: string, size: int, context: array<string, mixed>}): bool|null */
    private ?Closure $guestAuthorizer = null;

    public function __construct(
        private readonly AssetUploaders $uploaders,
        private readonly Assets $assets,
        private readonly AssetUploadHandler $uploads,
    ) {}

    /** @param Closure(Request, array{folderId: int|null, filename: string, size: int, context: array<string, mixed>}): bool $authorizer */
    public function allowGuestUploadsUsing(Closure $authorizer): void
    {
        $this->guestAuthorizer = $authorizer;
    }

    public function start(UploadRequest $request): UploadSession
    {
        abort_if(! $request->user() && $this->guestAuthorizer === null, 401);

        $data = $request->validated();

        $parameters = Arr::except($data, ['filename', 'size']);
        $parameters['operation'] ??= 'upload';
        $parameters = $this->authorize($request, $parameters, $data['filename'], (int) $data['size']);

        if (Cms::config()->uploadSessionDuration < 1) {
            throw new RuntimeException('uploadSessionDuration must be greater than zero.');
        }

        $diskReference = Cms::config()->getTempAssetUploadFs();
        $name = $this->uploaders->getDefaultDriver();
        $uploader = $this->uploaders->driver($name);

        $session = UploadSession::create([
            'id' => (string) Str::uuid(),
            'owner' => $this->owner($request),
            'uploader' => $name,
            'disk' => $diskReference,
            'filename' => $data['filename'],
            'size' => (int) $data['size'],
            'parameters' => $parameters,
            'state' => [],
            'expiresAt' => now()->addSeconds(Cms::config()->uploadSessionDuration),
        ]);

        $uploader->start($session);
        abort_unless($session->chunkSize > 0, 500, 'The uploader returned an invalid chunk size.');
        $session->save();

        return $session;
    }

    /** @return array<string, mixed> */
    public function part(Request $request, string $id, int $part): array
    {
        return $this->withSession($request, $id, function (UploadSession $session, AssetUploader $uploader) use ($part) {
            abort_if($session->result !== null, 409, 'This upload has already completed.');
            $session->partSize($part);

            return $uploader->partRequest($session, $part)->toArray();
        });
    }

    public function receive(Request $request, string $id, int $part): void
    {
        $this->withSession($request, $id, function (UploadSession $session, AssetUploader $uploader) use ($request, $part) {
            abort_unless($uploader instanceof ChunkedUploader, 409, 'This uploader does not receive bytes through PHP.');
            abort_if($session->result !== null, 409, 'This upload has already completed.');

            $stream = $request->getContent(asResource: true);

            try {
                $uploader->receive($session, $part, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        });
    }

    public function complete(Request $request, string $id): UploadResult
    {
        $result = $this->withSession($request, $id, function (UploadSession $session, AssetUploader $uploader) use ($request) {
            if ($session->result !== null) {
                return new UploadResult(...$session->result);
            }

            $file = $uploader->complete($session);

            try {
                abort_unless($file->size() === $session->size, 422, 'The uploaded file has an incorrect size.');
                $result = $session->parameters['operation'] === 'replace'
                    ? $this->uploads->replace((int) $session->parameters['assetId'], $file)
                    : $this->uploads->store(
                        $session->parameters,
                        $file->filename,
                        $file->mimeType(),
                        source: $file,
                        authorizedGuest: ! $request->user(),
                        uploaderId: $request->craftUser()?->getCraftUserId(),
                    );

                $session->result = $result->toArray();

                return $result;
            } finally {
                $file->release();
            }
        });

        try {
            $this->withSession($request, $id, function (UploadSession $session, AssetUploader $uploader) {
                $this->clean($session, $uploader);
            }, authorize: false);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $result;
    }

    public function cancel(Request $request, string $id): void
    {
        $this->withSession($request, $id, function (UploadSession $session, AssetUploader $uploader) {
            $this->clean($session, $uploader);
            $session->delete();
        }, authorize: false, allowExpired: true);
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
     * @param  Closure(UploadSession, AssetUploader): T  $callback
     * @return T
     */
    private function withSession(Request $request, string $id, Closure $callback, bool $authorize = true, bool $allowExpired = false): mixed
    {
        return DB::transaction(function () use ($request, $id, $callback, $authorize, $allowExpired) {
            $session = UploadSession::query()->lockForUpdate()->findOrFail($id);
            abort_unless(hash_equals($session->owner, $this->owner($request)), 404);
            abort_if(! $allowExpired && $session->expiresAt->isPast(), 410, 'The upload session has expired.');

            if ($authorize) {
                $parameters = $this->authorize($request, $session->parameters, $session->filename, $session->size);
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

    /** @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function authorize(Request $request, array $parameters, string $filename, int $size): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, Cms::config()->allowedFileExtensions, true), 422, 'This file extension is not allowed.');
        abort_if($size > AssetsHelper::getMaxAssetUploadSize(), 422, 'The file exceeds the maximum upload size.');

        if (! $request->user()) {
            abort_if($this->guestAuthorizer === null, 401);
            abort_unless($parameters['operation'] === 'upload', 403, 'Guest uploads cannot replace assets.');

            $parameters = [
                'operation' => 'upload',
                'folderId' => isset($parameters['folderId']) ? (int) $parameters['folderId'] : null,
                'context' => $parameters['context'] ?? [],
            ];

            abort_unless(($this->guestAuthorizer)($request, [
                'folderId' => $parameters['folderId'],
                'filename' => $filename,
                'size' => $size,
                'context' => $parameters['context'],
            ]) === true, 403);
        }

        if ($parameters['operation'] === 'replace') {
            $asset = $this->assets->getAssetById((int) ($parameters['assetId'] ?? 0));
            abort_unless($asset !== null, 404, 'Asset not found.');
            Gate::authorize('replaceFile', $asset);
        } else {
            $this->uploads->resolveTarget($parameters, authorizedGuest: ! $request->user());
        }

        return $parameters;
    }

    private function owner(Request $request): string
    {
        return $request->user()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'guest:'.hash('sha256', $request->session()->getId());
    }

    private function uploader(UploadSession $session): AssetUploader
    {
        return $this->uploaders->driver($session->uploader);
    }

    private function clean(UploadSession $session, AssetUploader $uploader): void
    {
        if (! ($session->state['cleaned'] ?? false)) {
            $uploader->abort($session);
            $session->state = [...$session->state, 'cleaned' => true];
        }
    }
}
