<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

use function CraftCms\Cms\template;

/**
 * An ordered table Control. Its canonical value is a list or keyed map of row
 * maps; cell values must be JSON-safe scalars or null.
 */
class Table extends Control
{
    /** @var array<string, array<string, mixed>> */
    private array $columns = [];

    private bool $allowAdd = false;

    private bool $allowDelete = false;

    private bool $allowReorder = false;

    private ?int $minRows = null;

    private ?int $maxRows = null;

    private bool $keyed = false;

    /** @var list<string> */
    private array $hiddenRows = [];

    /** @var array<string, mixed> */
    private array $defaultValues = [];

    /** @var array<string, array<string, true>> */
    private array $errors = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $rows = is_array($value)
            ? ((bool) ($control->props['keyed'] ?? false) ? $value : array_values($value))
            : [];

        return template('_includes/forms/editableTable', [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'cols' => $control->props['columns'],
            'rows' => $rows,
            'allowAdd' => (bool) ($control->props['allowAdd'] ?? false),
            'allowDelete' => (bool) ($control->props['allowDelete'] ?? false),
            'allowReorder' => (bool) ($control->props['allowReorder'] ?? false),
            'minRows' => $control->props['minRows'] ?? null,
            'maxRows' => $control->props['maxRows'] ?? null,
            'defaultValues' => $control->props['defaultValues'] ?? [],
            'static' => $attributes['name'] === null,
            'hiddenRows' => $control->props['hiddenRows'] ?? [],
            'errors' => $control->props['errors'] ?? [],
        ]);
    }

    public function component(): string
    {
        return 'craft:table';
    }

    /** @param array<string, array<string, mixed>> $columns */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function allowAdd(bool $allowAdd = true): static
    {
        $this->allowAdd = $allowAdd;

        return $this;
    }

    public function allowDelete(bool $allowDelete = true): static
    {
        $this->allowDelete = $allowDelete;

        return $this;
    }

    public function allowReorder(bool $allowReorder = true): static
    {
        $this->allowReorder = $allowReorder;

        return $this;
    }

    public function minRows(?int $minRows): static
    {
        $this->minRows = $minRows;

        return $this;
    }

    public function maxRows(?int $maxRows): static
    {
        $this->maxRows = $maxRows;

        return $this;
    }

    public function keyed(bool $keyed = true): static
    {
        $this->keyed = $keyed;

        return $this;
    }

    /**
     * Hides the given rows (by their row key — a shipping category id, say) without
     * removing them: their cells stay real inputs, still posting whatever they hold, so a
     * caller can stop hiding a row later without losing anything already typed in it —
     * the same principle {@see \CraftCms\Cms\Form\Nodes\Concerns\HasVisibility} documents
     * for whole Field/Group nodes.
     *
     * Deliberately a Control *prop* rather than part of each row's own value: props are
     * always freshly reapplied on a reactive refresh, whereas row-level values are only
     * ever merged in where missing (so an already-known row can't be updated this way
     * without touching real submitted data).
     *
     * @param  list<string>  $rowIds
     */
    public function hiddenRows(array $rowIds): static
    {
        $this->hiddenRows = $rowIds;

        return $this;
    }

    /** @param array<string, mixed> $defaultValues */
    public function defaultValues(array $defaultValues): static
    {
        $this->defaultValues = $defaultValues;

        return $this;
    }

    /** @param array<string, array<string, true>> $errors */
    public function errors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /** @return list<mixed> */
    #[\Override]
    public function emptyValue(): mixed
    {
        return [];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'columns' => $this->columns,
            'allowAdd' => $this->allowAdd,
            'allowDelete' => $this->allowDelete,
            'allowReorder' => $this->allowReorder,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'keyed' => $this->keyed,
            'hiddenRows' => $this->hiddenRows ?: null,
            'defaultValues' => $this->defaultValues,
            'errors' => $this->errors ?: null,
        ]);
    }
}
