<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\models\ImageTransform;
use craft\services\ImageTransforms as ImageTransformService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;

/**
 * @since 5.12.0
 */
class ImageTransforms
{
    public function __construct(private ImageTransformService $imageTransforms)
    {
    }

    /** @return array{count: int, transforms: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $transforms = collect($this->imageTransforms->getAllTransforms())
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $transforms->count(),
            'transforms' => $transforms->all(),
        ];
    }

    /** @return array{transform: array<string, mixed>} */
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        $transform = $this->find($id, $uid, $handle);

        if (!$transform) {
            throw new ToolCallException('Image transform not found.');
        }

        return ['transform' => $this->serialize($transform)];
    }

    /**
     * @param  int|null  $width  Transform width.
     * @param  int|null  $height  Transform height.
     * @param  string|null  $format  Output format.
     * @param  int|null  $quality  Output quality.
     * @param  string  $mode  Transform mode.
     * @param  string  $position  Crop or fit position.
     * @param  string  $interlace  Interlace mode.
     * @param  string|null  $fill  Fill color for letterbox mode.
     * @param  bool  $upscale  Whether images can be upscaled.
     * @return array{transform: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        #[Schema(minimum: 1)]
        ?int $width = null,
        #[Schema(minimum: 1)]
        ?int $height = null,
        #[Schema(enum: ['jpg', 'gif', 'png', 'webp', 'avif', 'bmp', 'heic', 'ico', 'jp2', 'jxl', 'tiff', null])]
        ?string $format = null,
        #[Schema(minimum: 1, maximum: 100)]
        ?int $quality = null,
        #[Schema(enum: ['crop', 'fit', 'stretch', 'letterbox'])]
        string $mode = 'crop',
        #[Schema(enum: ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'])]
        string $position = 'center-center',
        #[Schema(enum: ['none', 'line', 'plane', 'partition'])]
        string $interlace = 'none',
        ?string $fill = null,
        bool $upscale = true,
    ): array {
        $transform = new ImageTransform([
            'name' => $name,
            'handle' => $handle,
            'width' => $width,
            'height' => $height,
            'format' => $format,
            'quality' => $quality,
            'mode' => $mode,
            'position' => $position,
            'interlace' => $interlace,
            'fill' => $fill,
            'upscale' => $upscale,
        ]);

        if (!$this->imageTransforms->saveTransform($transform)) {
            throw new ToolCallException($this->validationErrors($transform));
        }

        return ['transform' => $this->serialize($transform)];
    }

    /**
     * @param  string|null  $currentHandle  Existing image transform handle.
     * @param  string|null  $handle  New transform handle.
     * @param  int|null  $width  Transform width.
     * @param  int|null  $height  Transform height.
     * @param  string|null  $format  Output format.
     * @param  int|null  $quality  Output quality.
     * @param  string  $mode  Transform mode.
     * @param  string  $position  Crop or fit position.
     * @param  string  $interlace  Interlace mode.
     * @param  string|null  $fill  Fill color for letterbox mode.
     * @param  bool  $upscale  Whether images can be upscaled.
     * @return array{transform: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        #[Schema(minimum: 1)]
        ?int $width = null,
        #[Schema(minimum: 1)]
        ?int $height = null,
        #[Schema(enum: ['jpg', 'gif', 'png', 'webp', 'avif', 'bmp', 'heic', 'ico', 'jp2', 'jxl', 'tiff', null])]
        ?string $format = null,
        #[Schema(minimum: 1, maximum: 100)]
        ?int $quality = null,
        #[Schema(enum: ['crop', 'fit', 'stretch', 'letterbox'])]
        string $mode = 'crop',
        #[Schema(enum: ['top-left', 'top-center', 'top-right', 'center-left', 'center-center', 'center-right', 'bottom-left', 'bottom-center', 'bottom-right'])]
        string $position = 'center-center',
        #[Schema(enum: ['none', 'line', 'plane', 'partition'])]
        string $interlace = 'none',
        ?string $fill = null,
        bool $upscale = true,
    ): array {
        $transform = $this->find($id, $uid, $currentHandle);

        if (!$transform) {
            throw new ToolCallException('Image transform not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Craft::configure($transform, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'width' => $width,
            'height' => $height,
            'format' => $format,
            'quality' => $quality,
            'mode' => $mode,
            'position' => $position,
            'interlace' => $interlace,
            'fill' => $fill,
            'upscale' => $upscale,
        ], $request->arguments));

        if (!$this->imageTransforms->saveTransform($transform)) {
            throw new ToolCallException($this->validationErrors($transform));
        }

        return ['transform' => $this->serialize($transform)];
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $transform = $this->find($id, $uid, $handle);

        if (!$transform) {
            throw new ToolCallException('Image transform not found.');
        }

        if (!$this->imageTransforms->deleteTransform($transform)) {
            throw new ToolCallException('Image transform could not be deleted.');
        }

        return ['deleted' => true];
    }

    private function find(?int $id = null, ?string $uid = null, ?string $handle = null): ?ImageTransform
    {
        return match (true) {
            $id !== null => $this->imageTransforms->getTransformById($id),
            $uid !== null => $this->imageTransforms->getTransformByUid($uid),
            $handle !== null => $this->imageTransforms->getTransformByHandle($handle),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function serialize(ImageTransform $transform): array
    {
        return [
            'id' => $transform->id,
            'uid' => $transform->uid,
            ...$transform->getConfig(),
        ];
    }

    private function validationErrors(ImageTransform $transform): string
    {
        return implode("\n", $transform->getErrorSummary(true)) ?: 'Image transform could not be saved.';
    }
}
