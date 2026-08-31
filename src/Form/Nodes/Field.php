<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Enums\FieldWidth;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Form\Nodes\Concerns\HasVisibility;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class Field implements Node
{
    use HasVisibility;

    private ?string $label = null;

    private ?string $instructions = null;

    private bool $required = false;

    private string $instructionsPosition = 'before';

    private ?string $tip = null;

    private ?string $warning = null;

    private ?string $layoutUid = null;

    private ?int $width = null;

    private ?string $status = null;

    private ?string $statusLabel = null;

    private ?Control $control = null;

    /** @var list<Node> */
    private array $actions = [];

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

        $actions = $node->children ?? [];

        return FieldComponent::make()
            ->actions($actions === [] ? null : new HtmlString($renderer->renderNodes($actions, $payload)))
            ->label($label)
            ->instructions($instructions)
            ->instructionsPosition((string) ($node->props['instructionsPosition'] ?? 'before'))
            ->tip(isset($node->props['tip']) ? (string) $node->props['tip'] : null)
            ->warning(isset($node->props['warning']) ? (string) $node->props['warning'] : null)
            ->required((bool) ($node->props['required'] ?? false))
            ->status(
                isset($node->props['status']) ? (string) $node->props['status'] : null,
                isset($node->props['statusLabel']) ? (string) $node->props['statusLabel'] : null,
            )
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

    public static function make(?string $label = null, ?Control $control = null): self
    {
        $field = new self;
        $field->label = $label;
        $field->control = $control;

        return $field;
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

    /**
     * Sets the field’s change-tracking status, shown as a badge beside the label.
     *
     * @param  string|null  $status  An {@see AttributeStatus} value
     * @param  string|null  $label  The human-facing description of the status
     */
    public function status(?string $status, ?string $label = null): static
    {
        $this->status = $status;
        $this->statusLabel = $status !== null ? $label : null;

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
                'instructionsPosition' => $this->instructionsPosition !== 'before' ? $this->instructionsPosition : null,
                'tip' => $this->tip,
                'tipHtml' => $this->noticeHtml($this->tip),
                'warning' => $this->warning,
                'warningHtml' => $this->noticeHtml($this->warning),
                'layoutUid' => $this->layoutUid,
                'width' => $this->width,
                'status' => $this->status,
                'statusLabel' => $this->status !== null ? $this->statusLabel : null,
                'hasActions' => $this->actions === [] ? null : true,
            ]),
            ...$this->visibilityProps(),
        ];
    }

    public function children(): array
    {
        return $this->actions;
    }

    private function noticeHtml(?string $notice): ?string
    {
        return $notice === null
            ? null
            : Html::decodeDoubles(Markdown::parseParagraph(Html::encodeInvalidTags($notice)));
    }
}
