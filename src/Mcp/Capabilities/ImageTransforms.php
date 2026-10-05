<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\ImageTransforms as ImageTransformService;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Support\Typecast;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class ImageTransforms
{
    public function __construct(private ImageTransformService $imageTransforms) {}

    /** @return array{transforms: list<array<string, mixed>>} */
    #[McpTool(
        name: 'image-transforms.list',
        description: 'Lists Craft CMS image transforms.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        return [
            'transforms' => $this->imageTransforms
                ->getAllTransforms()
                ->map($this->serialize(...))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  int|null  $id  Image transform ID.
     * @param  string|null  $uid  Image transform UID.
     * @param  string|null  $handle  Image transform handle.
     * @return array{transform: array<string, mixed>}
     */
    #[McpTool(
        name: 'image-transforms.get',
        description: 'Gets a Craft CMS image transform by ID, UID, or handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        if (count(array_filter([$id, $uid, $handle], static fn (mixed $value): bool => $value !== null)) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        $transform = $this->find($id, $uid, $handle);

        if (! $transform) {
            throw new ToolCallException('Image transform not found.');
        }

        return ['transform' => $this->serialize($transform)];
    }

    /**
     * @param  string  $name  Transform name.
     * @param  string  $handle  Transform handle.
     * @param  int|null  $width  Transform width.
     * @param  int|null  $height  Transform height.
     * @param  ImageTransformFormat|null  $format  Output format.
     * @param  int|null  $quality  Output quality.
     * @param  ImageTransformMode  $mode  Transform mode.
     * @param  ImageTransformPosition  $position  Crop or fit position.
     * @param  ImageTransformInterlace  $interlace  Interlace mode.
     * @param  string|null  $fill  Fill color for letterbox mode.
     * @param  bool  $upscale  Whether images can be upscaled.
     * @param  array<string, array<string, mixed>>  $parameters  Transformer-specific parameters keyed by asset transformer UID.
     * @return array{transform: array<string, mixed>}
     */
    #[McpTool(name: 'image-transforms.create', description: 'Creates a Craft CMS image transform.')]
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        #[Schema(minimum: 1)]
        ?int $width = null,
        #[Schema(minimum: 1)]
        ?int $height = null,
        ?ImageTransformFormat $format = null,
        #[Schema(minimum: 1, maximum: 100)]
        ?int $quality = null,
        ImageTransformMode $mode = ImageTransformMode::Crop,
        ImageTransformPosition $position = ImageTransformPosition::CenterCenter,
        ImageTransformInterlace $interlace = ImageTransformInterlace::None,
        ?string $fill = null,
        bool $upscale = true,
        #[Schema(
            type: 'object',
            additionalProperties: ['type' => 'object'],
        )]
        array $parameters = [],
    ): array {
        $transform = new ImageTransform([
            'name' => $name,
            'handle' => $handle,
            'width' => $width,
            'height' => $height,
            'format' => $format?->value,
            'quality' => $quality,
            'mode' => $mode->value,
            'position' => $position->value,
            'interlace' => $interlace->value,
            'fill' => $fill,
            'upscale' => $upscale,
            'parameters' => $parameters,
        ]);

        if (! $this->imageTransforms->saveTransform($transform)) {
            throw new ToolCallException($this->validationErrors($transform));
        }

        return ['transform' => $this->serialize($transform)];
    }

    /**
     * @param  int|null  $id  Image transform ID.
     * @param  string|null  $uid  Image transform UID.
     * @param  string|null  $currentHandle  Existing image transform handle.
     * @param  string|null  $name  Transform name.
     * @param  string|null  $handle  New transform handle.
     * @param  int|null  $width  Transform width.
     * @param  int|null  $height  Transform height.
     * @param  ImageTransformFormat|null  $format  Output format.
     * @param  int|null  $quality  Output quality.
     * @param  ImageTransformMode  $mode  Transform mode.
     * @param  ImageTransformPosition  $position  Crop or fit position.
     * @param  ImageTransformInterlace  $interlace  Interlace mode.
     * @param  string|null  $fill  Fill color for letterbox mode.
     * @param  bool  $upscale  Whether images can be upscaled.
     * @param  array<string, array<string, mixed>>  $parameters  Transformer-specific parameters keyed by asset transformer UID.
     * @return array{transform: array<string, mixed>}
     */
    #[McpTool(
        name: 'image-transforms.update',
        description: 'Updates a Craft CMS image transform.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
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
        ?ImageTransformFormat $format = null,
        #[Schema(minimum: 1, maximum: 100)]
        ?int $quality = null,
        ImageTransformMode $mode = ImageTransformMode::Crop,
        ImageTransformPosition $position = ImageTransformPosition::CenterCenter,
        ImageTransformInterlace $interlace = ImageTransformInterlace::None,
        ?string $fill = null,
        bool $upscale = true,
        #[Schema(
            type: 'object',
            additionalProperties: ['type' => 'object'],
        )]
        array $parameters = [],
    ): array {
        $transform = $this->find($id, $uid, $currentHandle);

        if (! $transform) {
            throw new ToolCallException('Image transform not found.');
        }

        $request = $context->getRequest();

        if (! $request instanceof CallToolRequest) {
            throw new ToolCallException('Invalid image transform request.');
        }

        Typecast::configure($transform, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'width' => $width,
            'height' => $height,
            'format' => $format?->value,
            'quality' => $quality,
            'mode' => $mode->value,
            'position' => $position->value,
            'interlace' => $interlace->value,
            'fill' => $fill,
            'upscale' => $upscale,
            'parameters' => $parameters,
        ], $request->arguments));

        if (! $this->imageTransforms->saveTransform($transform)) {
            throw new ToolCallException($this->validationErrors($transform));
        }

        return ['transform' => $this->serialize($transform)];
    }

    /**
     * @param  int|null  $id  Image transform ID.
     * @param  string|null  $uid  Image transform UID.
     * @param  string|null  $handle  Image transform handle.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'image-transforms.delete',
        description: 'Deletes a Craft CMS image transform.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $transform = $this->find($id, $uid, $handle);

        if (! $transform) {
            throw new ToolCallException('Image transform not found.');
        }

        if (! $this->imageTransforms->deleteTransform($transform)) {
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
        return implode("\n", $transform->errors()->all()) ?: 'Image transform could not be saved.';
    }
}
