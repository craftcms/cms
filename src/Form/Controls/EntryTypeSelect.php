<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\EntryTypes;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Support\Arr;

/**
 * An entry type picker, backed by the server-rendered
 * `_includes/forms/entryTypeSelect` component select.
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

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return self::selectHtml(
            is_array($value) ? $value : [],
            (bool) ($control->props['allowOverrides'] ?? false),
            (bool) ($control->props['create'] ?? false),
            $attributes['name'],
            $attributes['name'] === null,
        );
    }

    /** @param list<array<string, mixed>|int|string> $value */
    public static function selectHtml(
        array $value,
        bool $allowOverrides,
        bool $create,
        ?string $name,
        bool $disabled,
    ): string {
        $entryTypesService = app(EntryTypes::class);
        $entryTypes = array_values(array_filter(array_map(
            $entryTypesService->getEntryType(...),
            $value,
        )));
        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(fn (): string => FormFields::entryTypeSelectHtml([
            'id' => 'entry-types',
            'name' => ($name === null ? 'entryTypes' : self::leafName($name)).'[]',
            'values' => $entryTypes,
            'allowOverrides' => $allowOverrides,
            'create' => $create && ! $disabled,
            'disabled' => $disabled,
        ]), $namespace);
    }

    /**
     * Returns the value an entry type's chip posts, matching the hidden input
     * `_includes/forms/entryTypeSelect` renders when overrides are allowed.
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

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'allowOverrides' => $this->allowOverrides ?: null,
            'create' => $this->create ?: null,
        ]);
    }
}
