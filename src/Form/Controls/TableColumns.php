<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\NestedFormPayload;
use CraftCms\Cms\Form\Nodes\Field;

use function CraftCms\Cms\t;

/**
 * Edits Table column metadata and the settings Forms provided by cell types.
 *
 * @since 6.0.0
 */
class TableColumns extends Control
{
    /** @var list<array{label: string, value: string}> */
    private array $cellTypes = [];

    /** @var array<string, Form> */
    private array $columnForms = [];

    /** @var array<string, array<string, true>> */
    private array $errors = [];

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
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

    /** @param array<string, Form> $columnForms */
    public function columnForms(array $columnForms): static
    {
        $this->columnForms = $columnForms;

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
    public function nestsForms(): bool
    {
        return true;
    }

    #[\Override]
    public function resolveProps(mixed $value, ControlMode $mode): array
    {
        $template = app(FormResolver::class)->resolve(
            $this->columnForm(),
            new FormContext(mode: $mode),
        );

        return $this->props($value) + [
            'rowTemplate' => new NestedFormPayload(scope: [], refreshable: true, nodes: $template->nodes)->jsonSerialize(),
        ];
    }

    #[\Override]
    public function props(mixed $value = null): array
    {
        return ['cellTypes' => $this->cellTypes] + ($this->errors === [] ? [] : ['errors' => $this->errors]);
    }

    #[\Override]
    public function nestedForms(mixed $value = null): array
    {
        $forms = [];

        foreach (is_array($value) ? $value : [] as $key => $column) {
            $form = $this->columnForm($column);
            foreach (($this->columnForms[$key] ?? null)?->nodes() ?? [] as $node) {
                $form->add($node);
            }

            $forms[] = ['scope' => [(string) $key], 'form' => $form, 'refreshable' => true];
        }

        return $forms;
    }

    /** @param array<string, mixed> $column */
    public function columnForm(array $column = []): Form
    {
        $type = $column['type'] ?? 'singleline';
        $options = $this->cellTypes;
        if (isset($column['type']) && ! in_array($type, array_column($options, 'value'), true)) {
            $options[] = ['label' => t('Unavailable: {type}', ['type' => $type]), 'value' => $type];
        }

        return Form::make([
            Field::make(t('Heading'), Text::make('heading')->value($column['heading'] ?? '')),
            Field::make(t('Handle'), Handle::make('handle')->value($column['handle'] ?? '')),
            Field::make(t('Width'), Text::make('width')->value($column['width'] ?? '')),
            Field::make(t('Type'), Choice::make('type')->options($options)->withoutPlaceholder()->value($type)),
        ]);
    }
}
