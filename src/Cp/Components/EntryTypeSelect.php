<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;

use function CraftCms\Cms\t;

/**
 * A {@see ComponentSelect} of entry types: the PHP counterpart to the legacy
 * `_includes/forms/entryTypeSelect` template. Shows handles, offers every
 * entry type unless {@see self::options()} narrows them, and can let each
 * selection override the entry type's name, handle, and description.
 *
 *     EntryTypeSelect::make()
 *         ->name('entryTypes[]')
 *         ->values($section->getEntryTypes())
 *         ->allowOverrides()
 *         ->create();
 *
 * @since 6.0.0
 */
class EntryTypeSelect extends ComponentSelect
{
    #[\Override]
    protected bool $showHandles = true;

    protected bool $allowOverrides = false;

    protected bool $includeGroupInValues = false;

    /**
     * Whether each selection can override the entry type's name, handle, and
     * description. The chips then post `{id, name?, handle?, description?}`
     * JSON configs instead of IDs.
     */
    public function allowOverrides(bool $allowOverrides = true): static
    {
        $this->allowOverrides = $allowOverrides;

        return $this;
    }

    /** Whether the chips' JSON configs include the entry type's group. */
    public function includeGroupInValues(bool $includeGroupInValues = true): static
    {
        $this->includeGroupInValues = $includeGroupInValues;

        return $this;
    }

    /** Whether to offer a Create button for new entry types. */
    public function create(bool $create = true): static
    {
        return $this->createAction($create ? Url::cpUrl('settings/entry-types/new') : null);
    }

    #[\Override]
    protected function getOptions(): array
    {
        $this->options ??= app(EntryTypes::class)->getAllEntryTypes()->all();

        return parent::getOptions();
    }

    #[\Override]
    protected function chipConfig(Chippable $component): array
    {
        $config = parent::chipConfig($component);

        if (! $this->allowOverrides || ! $component instanceof EntryType) {
            return $config;
        }

        $overrides = $this->overrides($component);
        $value = array_filter([
            'id' => $component->id,
            'group' => $this->includeGroupInValues ? ($component->group ?? t('General')) : null,
        ]);

        return [
            ...$config,
            'inputValue' => Json::encode([...$value, ...$overrides]),
            'overrides' => $overrides,
        ];
    }

    /**
     * The name, handle, and description this selection overrides.
     *
     * @return array<string, string>
     */
    protected function overrides(EntryType $entryType): array
    {
        $original = $entryType->original;

        if ($original === null) {
            return [];
        }

        return array_filter([
            'name' => $entryType->name !== $original->name ? $entryType->name : null,
            'handle' => $entryType->handle !== $original->handle ? $entryType->handle : null,
            'description' => $entryType->description !== $original->description ? $entryType->description : null,
        ]);
    }
}
