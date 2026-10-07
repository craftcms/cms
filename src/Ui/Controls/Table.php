<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Ui\Contracts\Control as ControlContract;
use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use CraftCms\Cms\Ui\UiResolver;
use Illuminate\Support\Arr;

/**
 * An ordered table Control. Its canonical value is a list or keyed map of row
 * maps. Each configured cell is rendered by a shared Ui control.
 *
 * @since 6.0.0
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

    private ?string $addRowLabel = null;

    private bool|string $includeRowId = false;

    /** @var array<string, mixed> */
    private array $defaultValues = [];

    /** @var array<string, array<string, true>> */
    private array $errors = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $cellOptions = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        $values = [];
        $target = &$values;

        foreach ($control->path as $segment) {
            $target[$segment] = [];
            $target = &$target[$segment];
        }

        $target = $value ?? [];
        $payload = new UiPayload(
            scope: [],
            refreshable: false,
            nodes: [new NodePayload(type: Field::class, component: 'craft:field', props: [], control: $control)],
            values: $values,
            errors: $renderer->controlErrors($control->path),
            globalErrors: [],
        );

        return Html::tag('craft-table-ui', '', [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'data-payload' => Json::encode($payload),
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

    public function addRowLabel(?string $label): static
    {
        $this->addRowLabel = $label;

        return $this;
    }

    /** Includes new row UUIDs under `rowId` or the given field name. */
    public function includeRowId(bool|string $include = true): static
    {
        $this->includeRowId = $include;

        return $this;
    }

    public function hasColumns(): bool
    {
        return $this->columns !== [];
    }

    /** @return array<string, mixed> */
    public function rowDefaults(): array
    {
        $defaults = $this->defaultValues;

        foreach ($this->columns as $key => $column) {
            $control = $column['control'] ?? null;
            $defaults[$key] ??= $column['value'] ?? ($control instanceof ControlContract ? $control->getValue() : null);

            if (isset($column['prefixSelect'])) {
                $prefix = $column['prefixSelect'];
                $defaults[$prefix['key']] ??= $prefix['options'][0]['value'] ?? '';
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, array<string, mixed>>  $cellOptions
     */
    public function rowUi(array $row, array $cellOptions = []): Ui
    {
        $ui = Ui::make();

        foreach ($this->columns as $key => $column) {
            $column = array_replace($column, $cellOptions[$key] ?? []);
            if (($column['type'] ?? null) === 'html' || ($column['html'] ?? false)) {
                continue;
            }

            $control = TableColumn::control((string) $key, $column);
            if ($control instanceof Control) {
                $control->value($row[$key] ?? $this->rowDefaults()[$key] ?? null);
            }
            $ui->add(Field::make($column['heading'] ?? null, $control)->labelSrOnly()->required($column['required'] ?? false));

            if (isset($column['prefixSelect'])) {
                $prefix = $column['prefixSelect'];
                $ui->add(Field::make($prefix['label'], Choice::make([$prefix['key']])
                    ->options(TableColumn::options($prefix['options']))
                    ->withoutPlaceholder()
                    ->value($row[$prefix['key']] ?? $this->rowDefaults()[$prefix['key']] ?? null))->labelSrOnly());
            }
        }

        return $ui;
    }

    #[\Override]
    public function nestsUis(): bool
    {
        return $this->hasColumns();
    }

    #[\Override]
    public function nestedUis(mixed $value = null): array
    {
        if (! is_array($value) || ! $this->hasColumns()) {
            return [];
        }

        $uis = [];

        foreach ($value as $key => $row) {
            $uis[] = ['scope' => [(string) $key], 'ui' => $this->rowUi((array) $row, $this->cellOptions[$key] ?? []), 'refreshable' => false];
        }

        return $uis;
    }

    /** @param array<string, array<string, array<string, mixed>>> $cellOptions */
    public function cellOptions(array $cellOptions): static
    {
        $this->cellOptions = $cellOptions;

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
    public function resolveProps(mixed $value, ControlMode $mode): array
    {
        if (! $this->hasColumns()) {
            return $this->props($value);
        }

        $template = app(UiResolver::class)->resolve(
            $this->rowUi($this->rowDefaults()),
            new UiContext(mode: $mode),
        );

        return $this->props($value) + [
            'rowTemplate' => new NestedUiPayload(scope: [], refreshable: false, nodes: $template->nodes)->jsonSerialize(),
        ];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return Arr::whereNotNull([
            'columns' => array_map(fn (array $column): array => array_diff_key($column, ['control' => true]), $this->columns),
            'allowAdd' => $this->allowAdd,
            'allowDelete' => $this->allowDelete,
            'allowReorder' => $this->allowReorder,
            'minRows' => $this->minRows,
            'maxRows' => $this->maxRows,
            'keyed' => $this->keyed,
            'defaultValues' => $this->rowDefaults(),
            'addRowLabel' => $this->addRowLabel,
            'includeRowId' => $this->includeRowId,
            'errors' => $this->errors ?: null,
        ]);
    }
}
