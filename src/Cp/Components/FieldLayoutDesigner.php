<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FieldLayoutDesigner\FieldLayoutDesigner as Designer;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Support\Html;
use Override;

class FieldLayoutDesigner extends ViewComponent implements FormElement
{
    protected string|Closure|null $name = null;

    protected FieldLayout|Closure|null $fieldLayout = null;

    protected bool|Closure $withGeneratedFields = false;

    protected bool|Closure $readOnly = false;

    public static function formElementType(): string
    {
        return 'craft:field-layout-designer';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    public function name(string|Closure|null $name): static
    {
        $this->trackConfiguration('name');
        $this->name = $name;

        return $this;
    }

    public function fieldLayout(FieldLayout|Closure|null $fieldLayout): static
    {
        $this->fieldLayout = $fieldLayout;

        return $this;
    }

    public function withGeneratedFields(bool|Closure $withGeneratedFields = true): static
    {
        $this->withGeneratedFields = $withGeneratedFields;

        return $this;
    }

    public function readOnly(bool|Closure $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function toFormElementData(): FormElementData
    {
        $this->rejectConfiguredOptions(['attributes', 'slot'], 'Form');

        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form');
        }

        $fieldLayout = $this->resolvedFieldLayout('Form');
        $readOnly = $this->resolvedBool('readOnly', $this->readOnly, 'Form');
        $withGeneratedFields = $this->resolvedBool('withGeneratedFields', $this->withGeneratedFields, 'Form');
        $id = Html::id(sprintf('fld-%s', $fieldLayout->uid ?? spl_object_id($fieldLayout)));
        $designer = app(Designer::class);

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: array_filter([
                'designerHtml' => $designer->html($fieldLayout, [
                    'id' => $id,
                    'disabled' => $readOnly,
                ]),
                'generatedFieldsHtml' => $withGeneratedFields
                    ? $designer->generatedFieldsTableHtml($fieldLayout, [
                        'id' => "generated-fields-table-{$id}",
                        'disabled' => $readOnly,
                    ])
                    : null,
            ], fn (mixed $value): bool => $value !== null),
        );
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-field-layout-designer';
    }

    #[Override]
    protected function renderMarkup(): string
    {
        $this->rejectConfiguredOptions(['name', 'attributes', 'slot'], 'HTML');

        return app(Designer::class)->fieldHtml($this->resolvedFieldLayout('HTML'), [
            'withGeneratedFields' => $this->resolvedBool('withGeneratedFields', $this->withGeneratedFields, 'HTML'),
            'disabled' => $this->resolvedBool('readOnly', $this->readOnly, 'HTML'),
        ]);
    }

    private function resolvedFieldLayout(string $output): FieldLayout
    {
        $fieldLayout = $this->evaluate($this->fieldLayout);

        if (! $fieldLayout instanceof FieldLayout) {
            $this->unsupportedOutputOption('fieldLayout', $output);
        }

        return $fieldLayout;
    }

    private function resolvedBool(string $option, bool|Closure $value, string $output): bool
    {
        $value = $this->evaluate($value);

        if (! is_bool($value)) {
            $this->unsupportedOutputOption($option, $output);
        }

        return $value;
    }
}
