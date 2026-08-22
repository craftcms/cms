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
    public const array CORE_OPERATIONS = [
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
    private array $inlineOperations = [];

    /** @var array<string, array<string, mixed>> */
    private array $operations = [];

    /** @param array<string, mixed> $config */
    public static function fromConfig(array $config): self
    {
        return new self([
            'name' => $config['name'],
            'handle' => $config['handle'],
            ...Arr::only($config, self::CORE_OPERATIONS),
            'operations' => is_array($config['operations'] ?? null) ? $config['operations'] : [],
        ]);
    }

    public function getIsNamedTransform(): bool
    {
        return (bool) $this->id;
    }

    /** @return array<string, mixed> */
    public function getOperations(?string $transformerUid = null): array
    {
        $operations = [];

        foreach (self::CORE_OPERATIONS as $property) {
            $operations[$property] = in_array($property, ['height', 'width'], true)
                ? ($this->$property ?: null)
                : $this->$property;
        }

        return [
            ...$operations,
            ...$this->inlineOperations,
            ...($transformerUid !== null ? ($this->operations[$transformerUid] ?? []) : []),
        ];
    }

    /** @param array<string, mixed> $operations */
    public function setOperations(array $operations): void
    {
        $isTransformerMap = $operations === [] || array_all(
            array_keys($operations),
            fn (mixed $uid): bool => is_string($uid) && preg_match('/^[0-9a-f-]{36}$/i', $uid) === 1,
        );

        if ($isTransformerMap) {
            $this->operations = array_filter($operations, is_array(...));

            return;
        }

        $this->inlineOperations = [];

        foreach ($operations as $handle => $value) {
            if (in_array($handle, self::CORE_OPERATIONS, true)) {
                $this->$handle = $value;

                continue;
            }

            $this->inlineOperations[$handle] = $value;
        }
    }

    /** @return array<string, array<string, mixed>> */
    public function getCustomOperations(): array
    {
        return $this->operations;
    }

    /** @return array<string, mixed> */
    public function getOperationsForTransformer(string $uid): array
    {
        return $this->operations[$uid] ?? [];
    }

    /** @return array<string,mixed> */
    public function getConfig(): array
    {
        return [
            'name' => $this->name,
            'handle' => $this->handle,
            ...Arr::only($this->getOperations(), self::CORE_OPERATIONS),
            'operations' => $this->operations,
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
            'operations' => ['array'],
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
