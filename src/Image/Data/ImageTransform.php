<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Image\Contracts\AssetTransformerInterface;
use CraftCms\Cms\Image\ImageTransformer;
use DateTime;
use Illuminate\Validation\Rule;
use Override;

use function CraftCms\Cms\t;

class ImageTransform extends Component
{
    public const string DEFAULT_TRANSFORMER = 'craft';

    private const array POSITIONS = [
        'top-left',
        'top-center',
        'top-right',
        'center-left',
        'center-center',
        'center-right',
        'bottom-left',
        'bottom-center',
        'bottom-right',
    ];

    private const array MODES = [
        'crop',
        'fit',
        'stretch',
        'letterbox',
    ];

    private const array INTERLACES = ['none', 'line', 'plane', 'partition'];

    private const array FORMATS = ['jpg', 'gif', 'png', 'webp', 'avif'];

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

    public ?DateTime $parameterChangeTime = null;

    /** @var array<string, mixed> */
    public array $settings = [];

    protected ?string $transformer = null;

    public function getIsNamedTransform(): bool
    {
        return $this->id && in_array($this->getTransformer(), [null, self::DEFAULT_TRANSFORMER, ImageTransformer::class], true);
    }

    /**
     * Returns the available transform modes.
     *
     * @return array<string, string>
     */
    public static function modes(): array
    {
        return [
            'crop' => t('Crop'),
            'fit' => t('Fit'),
            'stretch' => t('Stretch'),
            'letterbox' => t('Letterbox'),
        ];
    }

    /**
     * Returns the transformer class.
     */
    public function getTransformer(): ?string
    {
        return $this->transformer;
    }

    /**
     * Sets the transformer class.
     *
     * @param  class-string<AssetTransformerInterface>|string|null  $transformer
     */
    public function setTransformer(?string $transformer): void
    {
        $this->transformer = $transformer === ImageTransformer::class || $transformer === null || $transformer === ''
            ? null
            : $transformer;
    }

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
            'settings' => ! empty($this->settings) ? $this->settings : null,
            'transformer' => $this->getTransformer(),
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
            'handle' => ['required', 'string'],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'mode' => ['required', Rule::in(self::MODES)],
            'position' => ['required', Rule::in(self::POSITIONS)],
            'interlace' => ['required', Rule::in(self::INTERLACES)],
            'quality' => ['nullable', 'integer', 'min:1', 'max:100'],
            'format' => ['nullable', Rule::in(self::FORMATS)],
        ];
    }
}
