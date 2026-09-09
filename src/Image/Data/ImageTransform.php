<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Image\Enums\ImageTransformFormat;
use CraftCms\Cms\Image\Enums\ImageTransformInterlace;
use CraftCms\Cms\Image\Enums\ImageTransformMode;
use CraftCms\Cms\Image\Enums\ImageTransformPosition;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Validation\Rules\HandleRule;
use DateTimeInterface;
use Illuminate\Validation\Rule;
use Override;

class ImageTransform extends Component
{
    public const array CORE_PARAMETERS = [
        'fill',
        'format',
        'height',
        'interlace',
        'mode',
        'position',
        'quality',
        'upscale',
        'width',
    ];

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

    /** @var array<string, mixed> */
    private array $inlineParameters = [];

    /** @var array<string, array<string, mixed>> */
    private array $parameters = [];

    /** @param array<string, mixed> $config */
    public static function fromConfig(array $config): self
    {
        return new self([
            'name' => $config['name'],
            'handle' => $config['handle'],
            ...Arr::only($config, self::CORE_PARAMETERS),
            'parameters' => is_array($config['parameters'] ?? null) ? $config['parameters'] : [],
        ]);
    }

    /** @param array<string, mixed> $parameters */
    public static function fromParameters(array $parameters): self
    {
        return new self()->setInlineParameters($parameters);
    }

    /** @param array<string, mixed> $parameters */
    public function setInlineParameters(array $parameters): static
    {
        foreach (Arr::only($parameters, self::CORE_PARAMETERS) as $handle => $value) {
            $this->$handle = $value;
        }

        $this->inlineParameters = Arr::except($parameters, self::CORE_PARAMETERS);

        return $this;
    }

    public function getIsNamedTransform(): bool
    {
        return (bool) $this->id;
    }

    /** @return array<string, mixed> */
    public function getParameters(?string $transformerUid = null): array
    {
        $parameters = [];

        foreach (self::CORE_PARAMETERS as $property) {
            $parameters[$property] = in_array($property, ['height', 'width'], true)
                ? ($this->$property ?: null)
                : $this->$property;
        }

        return [
            ...$parameters,
            ...$this->inlineParameters,
            ...($transformerUid !== null ? ($this->parameters[$transformerUid] ?? []) : []),
        ];
    }

    /** @param array<string, array<string, mixed>> $parameters */
    public function setParameters(array $parameters): void
    {
        $this->parameters = array_filter($parameters, is_array(...));
    }

    /** @return array<string, array<string, mixed>> */
    public function getCustomParameters(): array
    {
        return $this->parameters;
    }

    /** @return array<string, mixed> */
    public function getParametersForTransformer(string $uid): array
    {
        return $this->parameters[$uid] ?? [];
    }

    /** @return array<string,mixed> */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            ...Arr::only($this->getParameters(), self::CORE_PARAMETERS),
            'parameters' => $this->parameters,
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
            'parameters' => ['array'],
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
