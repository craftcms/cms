<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset;

use Closure;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Arr;
use Illuminate\Container\Attributes\Singleton;
use InvalidArgumentException;

/**
 * Registers additional asset file kinds or replaces built-in definitions.
 *
 * ```php
 * public function boot(AssetFileKinds $fileKinds): void
 * {
 *     $fileKinds->register('drawing', [
 *         'label' => 'Drawing',
 *         'extensions' => ['dwg'],
 *     ]);
 * }
 * ```
 */
#[Singleton]
class AssetFileKinds
{
    /** @var array<string, array{label?:string, extensions?:list<string>}|Closure():array{label?:string, extensions?:list<string>}> */
    private array $fileKinds = [];

    /** @var array<string, true> */
    private array $removedFileKinds = [];

    public function __construct(
        private readonly GeneralConfig $generalConfig,
    ) {}

    /**
     * @param  array{label?:string, extensions?:list<string>}|Closure():array{label?:string, extensions?:list<string>}  $definition
     */
    public function register(string $kind, array|Closure $definition): void
    {
        if ($kind === '') {
            throw new InvalidArgumentException('File kind names cannot be empty.');
        }

        unset($this->removedFileKinds[$kind]);
        $this->fileKinds[$kind] = $definition;
    }

    public function remove(string ...$kinds): void
    {
        foreach ($kinds as $kind) {
            $this->removedFileKinds[$kind] = true;
            unset($this->fileKinds[$kind]);
        }
    }

    /** @return array<string, array{label:string, extensions:list<string>}> */
    public function fileKinds(): array
    {
        $fileKinds = collect(FileKind::cases())
            ->filter(fn (FileKind $kind) => $kind !== FileKind::Unknown)
            ->mapWithKeys(fn (FileKind $kind) => [$kind->value => $kind->toArray()])
            ->all();

        $fileKinds = Arr::merge($fileKinds, $this->generalConfig->extraFileKinds);

        foreach ($this->fileKinds as $kind => $definition) {
            $fileKinds = Arr::merge($fileKinds, [
                $kind => $definition instanceof Closure ? app()->call($definition) : $definition,
            ]);
        }

        $fileKinds = array_diff_key($fileKinds, $this->removedFileKinds);

        foreach ($fileKinds as $kind => $definition) {
            if (! isset($definition['label'], $definition['extensions']) ||
                ! is_string($definition['label']) ||
                ! is_array($definition['extensions']) ||
                array_any($definition['extensions'], fn (mixed $extension) => ! is_string($extension))
            ) {
                throw new InvalidArgumentException("Invalid file kind definition [$kind].");
            }
        }

        return Arr::sort($fileKinds, 'label');
    }
}
