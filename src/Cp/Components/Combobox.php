<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Json;

/** PHP counterpart to the `<craft-combobox>` web component. */
class Combobox extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected ?string $value = null;

    /** @var list<array<string, mixed>> */
    protected array $options = [];

    protected ?string $placeholder = null;

    protected bool $required = false;

    protected bool $readOnly = false;

    protected int $limit = 150;

    protected bool $clearable = false;

    protected ?string $describedBy = null;

    protected function tagName(): string
    {
        return 'craft-combobox';
    }

    public function name(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function value(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /** @param list<array<string, mixed>> $options */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function clearable(bool $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    public function describedBy(?string $describedBy): static
    {
        $this->describedBy = $describedBy;

        return $this;
    }

    #[\Override]
    protected function hostAttributes(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->name,
            'model-value' => $this->value,
            'options' => Json::encode($this->options),
            'placeholder' => $this->placeholder,
            'required' => $this->required,
            'readonly' => $this->readOnly,
            'disabled' => $this->isDisabled(),
            'limit' => $this->limit,
            'clearable' => $this->clearable,
            'aria' => ['describedby' => $this->describedBy],
        ];
    }
}
