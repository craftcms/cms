<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Validation\Rules\HandleRule;
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Override;

class ImageTransform extends Component
{
    public const string DEFAULT_TRANSFORMER = ImageTransformer::class;

    public ?int $id = null;

    public ?string $name = null;

    public ?string $handle = null;

    public int|float|null $width = null;

    public int|float|null $height = null;

    public ?string $format = null;

    public ?int $quality = null;

    public string $mode = 'crop';

    public string $position = 'center-center';

    public string $interlace = 'none';

    public ?string $fill = null;

    public bool $upscale = true;

    public ?string $uid = null;

    public ?DateTimeInterface $parameterChangeTime = null;

    /** @var class-string<ImageTransformerInterface> */
    protected string $transformer = self::DEFAULT_TRANSFORMER;

    public function getIsNamedTransform(): bool
    {
        return $this->id && $this->getTransformer() === self::DEFAULT_TRANSFORMER;
    }

    /**
     * Returns the transformer class.
     *
     * @return class-string<ImageTransformerInterface>
     */
    public function getTransformer(): string
    {
        return $this->transformer;
    }

    /**
     * Sets the transformer class.
     *
     * @param  class-string<ImageTransformerInterface>|null  $transformer
     */
    public function setTransformer(?string $transformer): void
    {
        $this->transformer = $transformer ?? self::DEFAULT_TRANSFORMER;
    }

    public function getImageTransformer(): ImageTransformerInterface
    {
        return app()->make($this->getTransformer());
    }

    /** @return array<string,mixed> */
    public function getConfig(): array
    {
        return [
            'fill' => $this->fill,
            'format' => $this->format,
            'handle' => $this->handle,
            'height' => $this->height ?: null,
            'interlace' => $this->interlace,
            'mode' => $this->mode,
            'name' => $this->name,
            'position' => $this->position,
            'quality' => $this->quality,
            'upscale' => $this->upscale,
            'width' => $this->width ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRules(): array
    {
        return [
            'name' => ['required', 'string'],
            'handle' => ['required', 'string', new HandleRule, Rule::unique(Table::IMAGETRANSFORMS, 'handle')->ignore($this->id)],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'mode' => ['required', Rule::enum(ImageTransformMode::class)],
            'position' => ['required', Rule::enum(ImageTransformPosition::class)],
            'interlace' => ['required', Rule::enum(ImageTransformInterlace::class)],
            'quality' => ['nullable', 'integer', 'min:1', 'max:100'],
            'format' => ['nullable', Rule::enum(ImageTransformFormat::class)],
        ];
    }
}
