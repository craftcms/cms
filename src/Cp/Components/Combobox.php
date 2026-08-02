<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Cp\Forms\Contracts\FormElement;
use CraftCms\Cms\Cp\Forms\Data\FormElementData;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\HtmlString;

use function CraftCms\Cms\t;

class Combobox extends ViewComponent implements FormElement
{
    use HasDisabled;
    use HasId;

    protected string|Closure|null $name = null;

    /** @var array<array-key, mixed>|Closure */
    protected array|Closure $options = [];

    protected string|Closure|null $value = null;

    protected string|Closure|null $placeholder = null;

    protected bool|Closure $allowAliases = false;

    protected int|Closure $limit = 150;

    protected bool|Closure $clearable = false;

    protected string|Closure|null $labelledBy = null;

    protected string|Closure|null $describedBy = null;

    /** @var array<string, mixed> */
    protected array $formElementAttributes = [];

    public static function formElementType(): string
    {
        return 'craft:combobox-input';
    }

    public static function isFormElementContainer(): bool
    {
        return false;
    }

    protected function tagName(): string
    {
        return 'craft-combobox';
    }

    public function name(string|Closure|null $name): static
    {
        $this->name = $name;

        return $this;
    }

    /** @param array<array-key, mixed>|Closure $options */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function value(string|Closure|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function allowAliases(bool|Closure $allowAliases = true): static
    {
        $this->allowAliases = $allowAliases;

        return $this;
    }

    public function limit(int|Closure $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function clearable(bool|Closure $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    public function labelledBy(string|Closure|null $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    public function describedBy(string|Closure|null $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    /** @param array<string, mixed> $attributes */
    #[\Override]
    public function attributes(array $attributes): static
    {
        $this->formElementAttributes = [...$this->formElementAttributes, ...$attributes];

        return parent::attributes($attributes);
    }

    public function toFormElementData(): FormElementData
    {
        $name = $this->portableText('name', $this->name);

        if ($name === null) {
            $this->unsupportedOutputOption('name', 'Form');
        }

        $options = $this->evaluate($this->options);

        if (! is_array($options) || ! array_is_list($options)) {
            $this->unsupportedOutputOption('options', 'Form');
        }

        $limit = $this->evaluate($this->limit);
        $clearable = $this->evaluate($this->clearable);

        if (! is_int($limit) || $limit < 1) {
            $this->unsupportedOutputOption('limit', 'Form');
        }

        if (! is_bool($clearable)) {
            $this->unsupportedOutputOption('clearable', 'Form');
        }

        $attributes = $this->withoutAttributes($this->formElementAttributes, [
            'aria-describedby',
            'aria-labelledby',
            'disabled',
            'id',
            'model-value',
            'name',
            'readonly',
            'slot',
            'value',
        ]);

        $props = array_filter([
            'options' => $options,
            'placeholder' => $this->portableText('placeholder', $this->placeholder),
            'limit' => $limit === 150 ? null : $limit,
            'clearable' => $clearable ?: null,
        ], fn (mixed $value): bool => $value !== null);

        return new FormElementData(
            type: static::formElementType(),
            name: $name,
            props: $props,
            attributes: $attributes === [] ? null : $attributes,
        );
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        $options = $this->evaluate($this->options);

        if (! is_array($options)) {
            $this->unsupportedOutputOption('options', 'HTML');
        }

        return [
            'id' => $this->getId(),
            'name' => $this->evaluate($this->name),
            'model-value' => $this->evaluate($this->value),
            'options' => Json::encode($options),
            'limit' => $this->evaluate($this->limit),
            'clearable' => (bool) $this->evaluate($this->clearable),
            'placeholder' => $this->evaluate($this->placeholder),
            'disabled' => $this->isDisabled(),
            'aria' => [
                'labelledby' => $this->evaluate($this->labelledBy),
                'describedby' => $this->evaluate($this->describedBy),
            ],
        ];
    }

    #[\Override]
    protected function renderSlots(): string
    {
        if (! $this->evaluate($this->allowAliases)) {
            return parent::renderSlots();
        }

        $content = Html::encode(t('This can begin with an environment variable or alias.')).' '
            .Html::tag('a', Html::encode(t('Learn more')), [
                'href' => 'https://craftcms.com/docs/5.x/configure.html#control-panel-settings',
            ]);

        return Callout::make()
            ->slot('after')
            ->variant('info')
            ->appearance('plain')
            ->icon('lightbulb')
            ->attributes(['class' => 'p-0'])
            ->content(new HtmlString($content))
            ->toHtml()
            .parent::renderSlots();
    }
}
