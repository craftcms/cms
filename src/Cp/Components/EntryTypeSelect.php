<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use Override;

class EntryTypeSelect extends ViewComponent implements FormElement
{
    protected string|Closure|null $name = null;

    /** @var list<EntryType>|Closure */
    protected array|Closure $values = [];

    /** @var list<EntryType>|Closure */
    protected array|Closure $options = [];

    protected bool|Closure $allowOverrides = false;

    protected bool|Closure $includeGroupInValues = false;

    protected bool|Closure $create = false;

    protected bool|Closure $readOnly = false;

    public static function formElementType(): string
    {
        return 'craft:entry-type-select-input';
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

    /** @param list<EntryType>|Closure $values */
    public function values(array|Closure $values): static
    {
        $this->values = $values;

        return $this;
    }

    /** @param list<EntryType>|Closure $options */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function allowOverrides(bool|Closure $allowOverrides = true): static
    {
        $this->allowOverrides = $allowOverrides;

        return $this;
    }

    public function includeGroupInValues(bool|Closure $includeGroupInValues = true): static
    {
        $this->includeGroupInValues = $includeGroupInValues;

        return $this;
    }

    public function create(bool|Closure $create = true): static
    {
        $this->create = $create;

        return $this;
    }

    public function readOnly(bool|Closure $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function toFormElementData(): FormElementData
    {
        $name = $this->resolvedName('Form');
        $html = InputNamespace::namespaceInputs($this->selectHtml($name, 'Form'));

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: ['selectHtml' => $html],
            attributes: $this->attributes === [] ? null : $this->attributes,
        );
    }

    #[Override]
    protected function tagName(): string
    {
        return 'craft-component-select';
    }

    #[Override]
    protected function renderMarkup(): string
    {
        return $this->selectHtml($this->resolvedName('HTML'), 'HTML');
    }

    private function selectHtml(string $name, string $output): string
    {
        return FormFields::entryTypeSelectHtml([
            'id' => Html::id("entry-type-select-{$name}"),
            'name' => "{$name}[]",
            'values' => $this->resolvedEntryTypes('values', $this->values, $output),
            'options' => $this->resolvedEntryTypes('options', $this->options, $output),
            'allowOverrides' => $this->resolvedBool($this->allowOverrides),
            'includeGroupInValues' => $this->resolvedBool($this->includeGroupInValues),
            'create' => $this->resolvedBool($this->create),
            'disabled' => $this->resolvedBool($this->readOnly),
            'containerAttributes' => $output === 'HTML' ? $this->renderedAttributes() : [],
        ]);
    }

    private function resolvedName(string $output): string
    {
        $name = $this->evaluate($this->name);

        if ($name === '') {
            $this->invalidOutputOption('name', $output);
        }

        return $name;
    }

    /**
     * @param  list<EntryType>|Closure  $value
     * @return list<EntryType>
     */
    private function resolvedEntryTypes(string $option, array|Closure $value, string $output): array
    {
        $value = $this->evaluate($value);

        if (! array_is_list($value)) {
            $this->invalidOutputOption($option, $output);
        }

        foreach ($value as $entryType) {
            if (! $entryType instanceof EntryType) {
                $this->invalidOutputOption($option, $output);
            }
        }

        return $value;
    }
}
