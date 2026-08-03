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
        $name = $this->resolvedText($this->name);

        if ($name === null) {
            $this->invalidOutputOption('name', 'Form');
        }

        $fieldLayout = $this->resolvedFieldLayout();
        $readOnly = $this->resolvedBool($this->readOnly);
        $withGeneratedFields = $this->resolvedBool($this->withGeneratedFields);
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
        return app(Designer::class)->fieldHtml($this->resolvedFieldLayout(), [
            'withGeneratedFields' => $this->resolvedBool($this->withGeneratedFields),
            'disabled' => $this->resolvedBool($this->readOnly),
        ]);
    }

    private function resolvedFieldLayout(): FieldLayout
    {
        return $this->evaluate($this->fieldLayout);
    }
}
