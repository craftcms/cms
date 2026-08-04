<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use InvalidArgumentException;

class Field implements Node
{
    private ?string $label = null;

    private ?string $instructions = null;

    private bool $required = false;

    private ?Control $control = null;

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $control = $node->control;

        if ($control === null) {
            throw new InvalidArgumentException('Field Node requires a Control payload.');
        }

        $id = $renderer->id($control->path);
        $errors = $renderer->errorsFor($payload->errors, $control->path);
        $label = isset($node->props['label']) ? (string) $node->props['label'] : null;
        $instructions = isset($node->props['instructions']) ? (string) $node->props['instructions'] : null;
        $input = $renderer->renderControl(
            $control,
            $payload->values,
            $id,
            $errors !== [],
            (bool) ($node->props['required'] ?? false),
        );

        return FieldComponent::make()
            ->label($label)
            ->instructions($instructions)
            ->required((bool) ($node->props['required'] ?? false))
            ->readOnly($control->mode === ControlMode::ReadOnly)
            ->disabled($control->mode === ControlMode::Disabled)
            ->errors($errors)
            ->input($input)
            ->attributes(['data-mode' => $control->mode->value])
            ->toHtml();
    }

    public static function make(): self
    {
        return new self;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function instructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function control(Control $control): static
    {
        $this->control = $control;

        return $this;
    }

    public function getControl(): ?Control
    {
        return $this->control;
    }

    public function component(): string
    {
        return 'craft:field';
    }

    public function uid(): ?string
    {
        return null;
    }

    public function props(): array
    {
        return [
            'label' => $this->label,
            'instructions' => $this->instructions,
            'required' => $this->required,
        ];
    }

    public function children(): array
    {
        return [];
    }
}
