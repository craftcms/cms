<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Concerns\HasFieldPresentation;
use CraftCms\Cms\Ui\Nodes\Concerns\HasVisibility;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Stringable;

/**
 * @since 6.0.0
 */
class Field implements Node
{
    use Conditionable;
    use HasFieldPresentation;
    use HasVisibility;

    private bool $fieldset = false;

    private ?int $width = null;

    private ?Control $control = null;

    /** @var list<Node> */
    private array $actions = [];

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        $control = $node->control;

        if ($control === null) {
            throw new InvalidArgumentException('Field Node requires a Control payload.');
        }

        $id = $renderer->id($control->path);
        $errors = $renderer->errorsFor($payload->errors, $control->path);
        $input = $renderer->renderControl(
            $control,
            $payload->values,
            $renderer->inputId($control->path),
            $errors !== [],
            (bool) ($node->props['required'] ?? false),
        );

        $actions = $node->children ?? [];

        return self::fieldComponent($node->props)
            // Derived from the control's path, the same path the input's
            // name comes from. The input sits inside on `{$id}-input`.
            ->id($id)
            ->actions($actions === [] ? null : new HtmlString($renderer->renderNodes($actions, $payload)))
            ->fieldset((bool) ($node->props['fieldset'] ?? false))
            ->readOnly($control->mode === ControlMode::ReadOnly)
            ->disabled($control->mode === ControlMode::Disabled)
            ->errors($errors)
            ->input($input)
            ->attributes([
                'class' => isset($node->props['width']) ? "width-{$node->props['width']}" : null,
                'data-layout-element' => $node->props['layoutUid'] ?? null,
                'data-mode' => $control->mode->value,
            ])
            ->attributes(self::visibilityAttributes($node->props))
            ->toHtml();
    }

    public static function make(string|Htmlable|Stringable|null $label = null, ?Control $control = null): self
    {
        $field = new self;
        $field->label($label);
        $field->control = $control;

        return $field;
    }

    public function fieldset(bool $fieldset = true): static
    {
        $this->fieldset = $fieldset;

        return $this;
    }

    /**
     * Sets how wide the field should be within its container.
     *
     * Rendered as a `width-{n}` class and resolved by `<craft-field-group>`'s
     * twelve-column grid. Ints are accepted for layout elements, whose width
     * comes from project config; prefer {@see FieldWidth} in PHP.
     */
    public function width(FieldWidth|int|null $width): static
    {
        $this->width = $width instanceof FieldWidth ? $width->value : $width;

        return $this;
    }

    public function control(Control $control): static
    {
        $this->control = $control;

        return $this;
    }

    /**
     * Nodes rendered into the field heading's `actions` slot — hide-label
     * toggles, copy-value buttons, field settings menus.
     */
    public function actions(Node ...$actions): static
    {
        $this->actions = array_values($actions);

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
                'fieldset' => $this->fieldset ?: null,
                ...$this->fieldPresentationProps(),
                'width' => $this->width,
                'hasActions' => $this->actions === [] ? null : true,
            ]),
            ...$this->visibilityProps(),
        ];
    }

    public function children(): array
    {
        return $this->actions;
    }
}
