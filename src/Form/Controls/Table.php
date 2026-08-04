<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\FormHtmlRenderer;
use Illuminate\Support\Arr;

use function CraftCms\Cms\template;

/**
 * An ordered table Control. Its canonical value is a list of row maps keyed
 * by the authored column IDs; cell values must be JSON-safe scalars or null.
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

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        return template('_includes/forms/editableTable', [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'cols' => $control->props['columns'],
            'rows' => is_array($value) ? array_values($value) : [],
            'allowAdd' => (bool) ($control->props['allowAdd'] ?? false),
            'allowDelete' => (bool) ($control->props['allowDelete'] ?? false),
            'allowReorder' => (bool) ($control->props['allowReorder'] ?? false),
            'minRows' => $control->props['minRows'] ?? null,
            'maxRows' => $control->props['maxRows'] ?? null,
            'static' => $attributes['name'] === null,
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
        ]);
    }
}
