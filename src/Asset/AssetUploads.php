<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Singleton]
class AssetUploads implements UploadHandler
{
    /** @var Closure(Request, array{folderId: int|null, filename: string, size: int, context: array<string, mixed>}): bool|null */
    private ?Closure $guestAuthorizer = null;

    public function __construct(
        private readonly Assets $assets,
        private readonly AssetUploadHandler $uploads,
    ) {}

    /** @param Closure(Request, array{folderId: int|null, filename: string, size: int, context: array<string, mixed>}): bool $authorizer */
    public function allowGuestUploadsUsing(Closure $authorizer): void
    {
        $this->guestAuthorizer = $authorizer;
    }

    /** @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function authorize(Request $request, array $parameters, string $filename, int $size): array
    {
        abort_if(! $request->user() && $this->guestAuthorizer === null, 401);
        $parameters['operation'] ??= 'upload';

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

    public function complete(Request $request, array $parameters, UploadedFile $file): JsonResponse
    {
        $result = $parameters['operation'] === 'replace'
            ? $this->uploads->replace((int) $parameters['assetId'], $file)
            : $this->uploads->store(
                $parameters,
                $file,
                authorizedGuest: ! $request->user(),
                uploaderId: $request->craftUser()?->getCraftUserId(),
            );

        return new JsonResponse($result->payload(), $result->status);
    }
}
