<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Cp\Components\EntryTypeSelect as EntryTypeSelectComponent;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;

/**
 * An entry type picker, backed by the server-rendered
 * {@see EntryTypeSelectComponent}.
 *
 * The value is a list of entry type IDs, or — when overrides are allowed —
 * a list of `{id, name?, handle?, description?}` configs.
 *
 * @since 6.0.0
 */
class EntryTypeSelect extends Control
{
    private bool $allowOverrides = false;

    private bool $create = false;

    /** @var list<string> */
    private array $extraValueKeys = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return self::selectHtml(
            is_array($value) ? $value : [],
            (bool) ($control->props['allowOverrides'] ?? false),
            (bool) ($control->props['create'] ?? false),
            $attributes['name'],
            $attributes['name'] === null,
            $control->props['extraValueKeys'] ?? [],
        );
    }

    /**
     * @param  list<array<string, mixed>|int|string>  $value
     * @param  list<string>  $extraValueKeys  Additional keys that each selection value can hold, which are kept in the
     *                                        selections’ values (requires `$allowOverrides`)
     */
    public static function selectHtml(
        array $value,
        bool $allowOverrides,
        bool $create,
        ?string $name,
        bool $disabled,
        array $extraValueKeys = [],
    ): string {
        $entryTypesService = app(EntryTypes::class);
        $entryTypes = [];
        $extraValues = [];

        foreach ($value as $selection) {
            $entryType = $entryTypesService->getEntryType($selection);

            if (! $entryType) {
                continue;
            }

            $entryTypes[] = $entryType;

            if ($allowOverrides && $extraValueKeys !== [] && is_array($selection)) {
                $extraValues[$entryType->id] = Arr::only($selection, $extraValueKeys);
            }
        }

        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(fn (): string => EntryTypeSelectComponent::make()
            ->id('entry-types')
            ->name(($name === null ? 'entryTypes' : self::leafName($name)).'[]')
            ->values($entryTypes)
            ->allowOverrides($allowOverrides)
            ->extraValues($extraValues)
            ->showIndicators($allowOverrides)
            ->showDescription($allowOverrides)
            ->create($create && ! $disabled)
            ->disabled($disabled)
            ->checkboxOptions()
            ->toHtml(), $namespace);
    }

    /**
     * Returns the value an entry type's chip posts, matching the hidden input
     * {@see EntryTypeSelectComponent} renders when overrides are allowed.
     *
     * @return array<string, int|string|null>
     */
    public static function selectionValue(EntryType $entryType): array
    {
        $original = $entryType->original;

        return [
            'id' => (int) $entryType->id,
            ...Arr::whereNotNull([
                'name' => $original && $entryType->name !== $original->name ? $entryType->name : null,
                'handle' => $original && $entryType->handle !== $original->handle ? $entryType->handle : null,
                'description' => $original && $entryType->description !== $original->description ? $entryType->description : null,
            ]),
        ];
    }

    /** @return list<array<string, mixed>|int> */
    #[\Override]
    public function emptyValue(): mixed
    {
        return [];
    }

    public function component(): string
    {
        return 'craft:entry-type-select';
    }

    /** Whether each selected entry type's name, handle, and description can be overridden. */
    public function allowOverrides(bool $allowOverrides = true): static
    {
        $this->allowOverrides = $allowOverrides;

        return $this;
    }

    /** Whether the picker offers a "create a new entry type" action. */
    public function create(bool $create = true): static
    {
        $this->create = $create;

        return $this;
    }

    /**
     * Additional keys that each selection value can hold alongside the entry type’s ID and overrides
     * (e.g. a plugin’s per-entry-type setting), which are kept when the selections are re-rendered.
     *
     * Only applies when overrides are allowed, since selection values are otherwise bare IDs.
     *
     * @param  list<string>  $keys
     */
    public function extraValueKeys(array $keys): static
    {
        $this->extraValueKeys = array_values($keys);

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'allowOverrides' => $this->allowOverrides ?: null,
            'create' => $this->create ?: null,
            'extraValueKeys' => $this->extraValueKeys ?: null,
        ]);
    }
}
