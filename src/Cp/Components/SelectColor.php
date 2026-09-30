<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Json;

class SelectColor extends ViewComponent
{
    use HasDisabled;
    use HasId;

    protected ?string $name = null;

    protected ?string $value = null;

    protected ?string $label = null;

    protected bool $allowTransparent = false;

    protected ?string $blankLabel = null;

    /** @var list<string>|null */
    protected ?array $colors = null;

    protected bool $required = false;

    protected bool $readOnly = false;

    protected ?string $describedBy = null;

    protected function tagName(): string
    {
        return 'craft-select-color';
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

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function allowTransparent(bool $allowTransparent = true): static
    {
        $this->allowTransparent = $allowTransparent;

        return $this;
    }

    public function blankLabel(?string $blankLabel): static
    {
        $this->blankLabel = $blankLabel;

        return $this;
    }

    /**
     * @param  list<string>|null  $colors
     */
    public function colors(?array $colors): static
    {
        $this->colors = $colors;

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
            'label' => $this->label,
            'allow-transparent' => $this->allowTransparent,
            'blank-label' => $this->blankLabel,
            'colors' => $this->colors !== null ? Json::encode($this->colors) : null,
            'required' => $this->required,
            'readonly' => $this->readOnly,
            'disabled' => $this->isDisabled(),
            'aria' => ['describedby' => $this->describedBy],
        ];
    }
}
