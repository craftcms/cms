<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Controls;

use CraftCms\Cms\Ui\ControlPayload;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\NestedUiPayload;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;

use function CraftCms\Cms\t;

/**
 * Edits Table column metadata and the settings UIs provided by cell types.
 *
 * @since 6.0.0
 */
class TableColumns extends Control
{
    /** @var list<array{label: string, value: string}> */
    private array $cellTypes = [];

    /** @var array<string, Ui> */
    private array $columnUis = [];

    /** @var array<string, array<string, true>> */
    private array $errors = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, UiHtmlRenderer $renderer): string
    {
        return Table::renderHtml($control, $value, $attributes, $renderer);
    }

    public function component(): string
    {
        return 'craft:table-columns';
    }

    /** @param list<array{label: string, value: string}> $cellTypes */
    public function cellTypes(array $cellTypes): static
    {
        $this->cellTypes = $cellTypes;

        return $this;
    }

    /** @param array<string, Ui> $columnUis */
    public function columnUis(array $columnUis): static
    {
        $this->columnUis = $columnUis;

        return $this;
    }

    /** @param array<string, array<string, true>> $errors */
    public function errors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    #[\Override]
    public function emptyValue(): mixed
    {
        return [];
    }

    #[\Override]
    public function nestsUis(): bool
    {
        return true;
    }

    #[\Override]
    public function resolveProps(mixed $value, ControlMode $mode): array
    {
        $template = app(UiResolver::class)->resolve(
            $this->columnUi(),
            new UiContext(mode: $mode),
        );

        return $this->props($value) + [
            'rowTemplate' => new NestedUiPayload(scope: [], refreshable: true, nodes: $template->nodes)->jsonSerialize(),
        ];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return ['cellTypes' => $this->cellTypes] + ($this->errors === [] ? [] : ['errors' => $this->errors]);
    }

    #[\Override]
    public function nestedUis(mixed $value = null): array
    {
        $uis = [];

        foreach (is_array($value) ? $value : [] as $key => $column) {
            $ui = $this->columnUi($column);
            foreach (($this->columnUis[$key] ?? null)?->nodes() ?? [] as $node) {
                $ui->add($node);
            }

            $uis[] = ['scope' => [(string) $key], 'ui' => $ui, 'refreshable' => true];
        }

        return $uis;
    }

    /** @param array<string, mixed> $column */
    public function columnUi(array $column = []): Ui
    {
        $type = $column['type'] ?? 'singleline';
        $options = $this->cellTypes;
        if (isset($column['type']) && ! in_array($type, array_column($options, 'value'), true)) {
            $options[] = ['label' => t('Unavailable: {type}', ['type' => $type]), 'value' => $type];
        }

        return Ui::make([
            Field::make(t('Heading'), Text::make('heading')->value($column['heading'] ?? '')),
            Field::make(t('Handle'), Handle::make('handle')->value($column['handle'] ?? '')),
            Field::make(t('Width'), Text::make('width')->value($column['width'] ?? '')),
            Field::make(t('Type'), Choice::make('type')->options($options)->withoutPlaceholder()->value($type)),
        ]);
    }
}
