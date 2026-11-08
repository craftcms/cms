<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Concerns\HasDisabled;
use CraftCms\Cms\Cp\Concerns\HasId;
use CraftCms\Cms\Support\Json;

/**
 * @since 6.0.0
 */
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

    /** @var array<string, string> */
    protected array $labels = [];

    protected bool $required = false;

    protected bool $readOnly = false;

    protected bool $invalid = false;

    protected ?string $describedBy = null;

    protected ?string $labelledBy = null;

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
     * @param  list<string>|null  $colors  Palette color names, or hex colors such as `#e5422b`.
     */
    public function colors(?array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    /**
     * Names for the colors, by color. Palette colors are otherwise named after
     * themselves, and hex colors by their values.
     *
     * @param  array<string, string>  $labels
     */
    public function labels(array $labels): static
    {
        $this->labels = $labels;

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

    /**
     * Names the element labelling this control, for a caller that provides the
     * label itself rather than through the `label` attribute — a table column
     * heading, say.
     */
    public function labelledBy(?string $labelledBy): static
    {
        $this->labelledBy = $labelledBy;

        return $this;
    }

    /**
     * Marks the value as having failed validation, such as on the server.
     */
    public function invalid(bool $invalid = true): static
    {
        $this->invalid = $invalid;

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
            'labels' => $this->labels !== [] ? Json::encode($this->labels) : null,
            'required' => $this->required,
            'readonly' => $this->readOnly,
            'aria-invalid' => $this->invalid ? 'true' : null,
            'disabled' => $this->isDisabled(),
            'described-by' => $this->describedBy,
            'labelled-by' => $this->labelledBy,
        ];
    }
}
