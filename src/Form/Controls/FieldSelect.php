<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\InputNamespace;
use Illuminate\Support\Arr;

/**
 * A custom field picker, backed by the server-rendered
 * `_includes/forms/fieldSelect` component select.
 *
 * The value is the selected field's ID.
 */
class FieldSelect extends Control
{
    private ?int $limit = 1;

    private bool $create = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return self::selectHtml(
            is_numeric($value) ? (int) $value : null,
            $control->props['limit'] ?? null,
            (bool) ($control->props['create'] ?? false),
            $attributes['name'],
            $attributes['name'] === null,
        );
    }

    public static function selectHtml(
        ?int $value,
        ?int $limit,
        bool $create,
        ?string $name,
        bool $disabled,
    ): string {
        $field = $value === null ? null : Fields::getFieldById($value);
        $namespace = $name === null ? null : self::parentInputName($name);

        return InputNamespace::namespaceInputs(fn (): string => FormFields::fieldSelectHtml([
            'id' => 'field-select',
            'name' => $name === null ? 'fieldId' : self::leafName($name),
            'value' => $field,
            'limit' => $limit,
            'create' => $create,
            'disabled' => $disabled,
        ]), $namespace);
    }

    public function component(): string
    {
        return 'craft:field-select';
    }

    public function limit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    /** Whether the picker offers a "create a new field" action. */
    public function create(bool $create = true): static
    {
        $this->create = $create;

        return $this;
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'limit' => $this->limit,
            'create' => $this->create ?: null,
        ]);
    }
}
