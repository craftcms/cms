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
use Illuminate\Support\Arr;
use InvalidArgumentException;

class Field implements Node
{
    private ?string $label = null;

    private ?string $instructions = null;

    private bool $required = false;

    private string $instructionsPosition = 'before';

    private ?string $tip = null;

    private ?string $warning = null;

    private ?string $layoutUid = null;

    private ?int $width = null;

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
            ->instructionsPosition((string) ($node->props['instructionsPosition'] ?? 'before'))
            ->tip(isset($node->props['tip']) ? (string) $node->props['tip'] : null)
            ->warning(isset($node->props['warning']) ? (string) $node->props['warning'] : null)
            ->required((bool) ($node->props['required'] ?? false))
            ->readOnly($control->mode === ControlMode::ReadOnly)
            ->disabled($control->mode === ControlMode::Disabled)
            ->errors($errors)
            ->input($input)
            ->attributes([
                'class' => isset($node->props['width']) ? "width-{$node->props['width']}" : null,
                'data-layout-element' => $node->props['layoutUid'] ?? null,
                'data-mode' => $control->mode->value,
            ])
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

    public function instructionsPosition(string $instructionsPosition): static
    {
        $this->instructionsPosition = $instructionsPosition;

        return $this;
    }

    public function tip(?string $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    public function warning(?string $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    public function layoutUid(?string $layoutUid): static
    {
        $this->layoutUid = $layoutUid;

        return $this;
    }

    public function width(?int $width): static
    {
        $this->width = $width;

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
            ...Arr::whereNotNull([
                'instructionsPosition' => $this->instructionsPosition !== 'before' ? $this->instructionsPosition : null,
                'tip' => $this->tip,
                'warning' => $this->warning,
                'layoutUid' => $this->layoutUid,
                'width' => $this->width,
            ]),
        ];
    }

    public function children(): array
    {
        return [];
    }
}
