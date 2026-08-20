<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Cp\Components\Combobox as ComboboxComponent;
use CraftCms\Cms\Cp\Components\FilesystemSelect as FilesystemSelectComponent;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Http\Controllers\Settings\FilesystemsController;
use CraftCms\Cms\Support\Facades\Filesystems;

use function CraftCms\Cms\t;

class FilesystemSelect extends Combobox
{
    private bool $create = false;

    private bool $includeEnvVars = false;

    /** @var list<string> */
    private array $disabledTargets = [];

    private ?string $emptyOption = null;

    public function component(): string
    {
        return 'craft:filesystem-select';
    }

    public function create(bool $create = true): static
    {
        $this->create = $create;

        return $this;
    }

    public function includeEnvVars(bool $includeEnvVars = true): static
    {
        $this->includeEnvVars = $includeEnvVars;

        return $this;
    }

    /** @param list<string> $disabledTargets */
    public function disabledTargets(array $disabledTargets): static
    {
        $this->disabledTargets = $disabledTargets;

        return $this;
    }

    public function emptyOption(?string $label): static
    {
        $this->emptyOption = $label;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        $this
            ->options($this->filesystemOptions($value))
            ->showAllOnEmpty()
            ->showSelectedHint();

        return [
            ...parent::props($value),
            ...($this->create ? ['createUrl' => action([FilesystemsController::class, 'create'])] : []),
        ];
    }

    #[\Override]
    protected static function htmlComponent(ControlPayload $control): ComboboxComponent
    {
        return FilesystemSelectComponent::make()
            ->createUrl($control->props['createUrl'] ?? null);
    }

    /** @return list<array<string, mixed>> */
    private function filesystemOptions(mixed $value): array
    {
        $selectedTarget = $this->filesystemTarget($value);
        $options = collect(SelectOptions::getFsOptions())
            ->map(function (array $option) use ($selectedTarget): array {
                $target = $this->filesystemTarget($option['value']);

                return [
                    ...$option,
                    'disabled' => $target !== null
                        && $target !== $selectedTarget
                        && in_array($target, $this->disabledTargets, true),
                    'data' => ['hint' => $option['value']],
                ];
            });
        $craftFilesystems = $options
            ->reject(fn (array $option): bool => str_starts_with($option['value'], Volume::STORAGE_DISK_PREFIX))
            ->values();

        if ($this->create) {
            $craftFilesystems->push([
                'label' => t('Create a new filesystem…'),
                'value' => '__add__',
                'disabled' => false,
                'data' => ['hint' => ''],
            ]);
        }

        $items = $this->emptyOption === null ? [] : [[
            'label' => $this->emptyOption,
            'value' => '',
            'data' => ['hint' => ''],
        ]];
        $items[] = [
            'type' => 'optgroup',
            'label' => t('Craft Filesystems'),
            'options' => $craftFilesystems->all(),
        ];
        $disks = $options
            ->filter(fn (array $option): bool => str_starts_with($option['value'], Volume::STORAGE_DISK_PREFIX))
            ->values()
            ->all();

        if ($disks !== []) {
            $items[] = [
                'type' => 'optgroup',
                'label' => t('Laravel Disks'),
                'options' => $disks,
            ];
        }

        return $this->includeEnvVars
            ? [...$items, ...SelectOptions::getEnvOptions($options->pluck('value')->all())]
            : $items;
    }

    private function filesystemTarget(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? Filesystems::resolveDiskName($value)
            : null;
    }
}
