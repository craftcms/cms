<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Closure;
use CraftCms\Cms\Asset\Analysis\Data\ImageAnalysis;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Filesystem\Contracts\UploadHandler;
use CraftCms\Cms\Filesystem\Data\UploadedFile;
use CraftCms\Cms\Image\Data\ImageColors;
use CraftCms\Cms\Image\Data\ImageMetadata;
use CraftCms\Cms\Image\ImageHash;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

/**
 * @since 6.0.0
 */
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
        $analysis = $this->uploadedAnalysis($request);

        $result = $parameters['operation'] === 'replace'
            ? $this->uploads->replace((int) $parameters['assetId'], $file, $analysis)
            : $this->uploads->store(
                $parameters,
                $file,
                authorizedGuest: ! $request->user(),
                uploaderId: $request->craftUser()?->getCraftUserId(),
                analysis: $analysis,
            );

        return new JsonResponse($result->payload(), $result->status);
    }

    /**
     * Returns what the uploader read from the file, if it sent anything: its colors, difference hash, and embedded
     * metadata.
     *
     * Only those parts are taken, never what AI tasks find, such as keywords, which can’t be set from a request.
     */
    private function uploadedAnalysis(Request $request): ?ImageAnalysis
    {
        $colors = $this->uploadedColors($request);
        // Only trusted alongside colors, since the file is sampled on the server otherwise
        $hash = $colors !== null ? $this->uploadedHash($request) : null;
        $metadata = $request->input('analysis.metadata');
        $metadata = is_array($metadata) ? ImageMetadata::fromArray($metadata) : null;

        return $colors !== null || $metadata !== null ? new ImageAnalysis(colors: $colors, hash: $hash, metadata: $metadata) : null;
    }

    /**
     * Returns the colors the uploader sampled from the file, if it sent any.
     *
     * They come from the client, so anything that isn't a grid of up to 4×4 hex colors, with luminance ranges and
     * palette weights from 0 to 1 if there are any, is ignored, and the file is sampled on the server instead.
     */
    private function uploadedColors(Request $request): ?ImageColors
    {
        $hexColor = 'regex:/^#[0-9a-f]{6}([0-9a-f]{2})?$/i';
        $validator = Validator::make(['colors' => $request->input('analysis.colors')], [
            'colors' => ['required', 'array:dominant,grid,palette,luminance,weights'],
            'colors.dominant' => ['nullable', 'string', $hexColor],
            'colors.grid' => ['required', 'array', 'list', 'min:1', 'max:4'],
            'colors.grid.*' => ['array', 'list', 'min:1', 'max:4'],
            'colors.grid.*.*' => ['string', $hexColor],
            'colors.palette' => ['nullable', 'array', 'list', 'max:5'],
            'colors.palette.*' => ['string', $hexColor],
            'colors.luminance' => ['nullable', 'array', 'list', 'max:4'],
            'colors.luminance.*' => ['array', 'list', 'max:4'],
            'colors.luminance.*.*' => ['nullable', 'array', 'list', 'size:2'],
            'colors.luminance.*.*.*' => ['numeric', 'between:0,1'],
            'colors.weights' => ['nullable', 'array', 'list', 'max:5'],
            'colors.weights.*' => ['numeric', 'between:0,1'],
        ]);

        if ($validator->fails()) {
            return null;
        }

        $colors = $validator->validated()['colors'];

        if (count(array_unique(array_map(count(...), $colors['grid']))) > 1) {
            return null;
        }

        return ImageColors::fromArray($colors);
    }

    /**
     * Returns the difference hash the uploader found for the file, if it sent a valid one.
     */
    private function uploadedHash(Request $request): ?string
    {
        $hash = $request->input('analysis.hash');

        return ImageHash::isHash($hash) ? $hash : null;
    }
}
