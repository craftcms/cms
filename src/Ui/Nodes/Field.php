<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Element\Enums\AttributeStatus;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Concerns\HasVisibility;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Stringable;
use Twig\Markup;

/**
 * @since 6.0.0
 */
class Field implements Node
{
    use Conditionable;
    use HasVisibility;

    private ?string $label = null;

    private ?string $labelHtml = null;

    private bool $labelSrOnly = false;

    private bool $fieldset = false;

    private bool $showStatus = true;

    private ?string $headingPrefix = null;

    private ?string $headingSuffix = null;

    private bool $translatable = false;

    private ?string $translationDescription = null;

    private ?string $orientation = null;

    private ?string $inputWidth = null;

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

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
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
            $renderer->inputId($control->path),
            $errors !== [],
            (bool) ($node->props['required'] ?? false),
        );

        $actions = $node->children ?? [];

        return FieldComponent::make()
            // Derived from the control's path, the same path the input's
            // name comes from. The input sits inside on `{$id}-input`.
            ->id($id)
            ->actions($actions === [] ? null : new HtmlString($renderer->renderNodes($actions, $payload)))
            ->label(isset($node->props['labelHtml']) ? new HtmlString($node->props['labelHtml']) : $label)
            ->labelSrOnly((bool) ($node->props['labelSrOnly'] ?? false))
            ->fieldset((bool) ($node->props['fieldset'] ?? false))
            ->headingPrefix(isset($node->props['headingPrefix']) ? (string) $node->props['headingPrefix'] : null)
            ->headingSuffix(isset($node->props['headingSuffix']) ? (string) $node->props['headingSuffix'] : null)
            ->translatable((bool) ($node->props['translatable'] ?? false), $node->props['translationDescription'] ?? null)
            ->orientation($node->props['orientation'] ?? null)
            ->width($node->props['inputWidth'] ?? null)
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

    public static function make(string|Htmlable|Stringable|null $label = null, ?Control $control = null): self
    {
        $field = new self;
        $field->label($label);
        $field->control = $control;

        return $field;
    }

    /** Strings are plain text; Htmlable and Twig markup render as trusted HTML. */
    public function label(string|Htmlable|Stringable|null $label): static
    {
        $this->labelHtml = match (true) {
            $label instanceof Htmlable => $label->toHtml(),
            $label instanceof Markup => (string) $label,
            default => null,
        };
        $this->label = $this->labelHtml !== null
            ? html_entity_decode(strip_tags($this->labelHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : ($label !== null ? (string) $label : null);

        return $this;
    }

    /** Visually hides the label, keeping it available to screen readers. */
    public function labelSrOnly(bool $labelSrOnly = true): static
    {
        $this->labelSrOnly = $labelSrOnly;

        return $this;
    }

    /**
     * Parses markdown and preserves inline HTML, like {@see tip()} and {@see warning()}.
     */
    public function instructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * Trusted HTML rendered beside the label, such as a `<craft-info-icon>`.
     * Encode any user-provided content before including it in the markup.
     */
    public function headingSuffix(string|Htmlable|null $headingSuffix): static
    {
        $this->headingSuffix = $headingSuffix instanceof Htmlable ? $headingSuffix->toHtml() : $headingSuffix;

        return $this;
    }

    /** Trusted HTML rendered before the label. Encode any user-provided content. */
    public function headingPrefix(string|Htmlable|null $headingPrefix): static
    {
        $this->headingPrefix = $headingPrefix instanceof Htmlable ? $headingPrefix->toHtml() : $headingPrefix;

        return $this;
    }

    public function translatable(bool $translatable = true, ?string $description = null): static
    {
        $this->translatable = $translatable;

        if ($description !== null) {
            $this->translationDescription = $description;
        }

        return $this;
    }

    public function fieldset(bool $fieldset = true): static
    {
        $this->fieldset = $fieldset;

        return $this;
    }

    public function showStatus(bool $showStatus = true): static
    {
        $this->showStatus = $showStatus;

        return $this;
    }

    /** @param 'ltr'|'rtl'|null $orientation */
    public function orientation(?string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Overrides control-based sizing within the field's grid allocation.
     * `full` fills the column even with a maxlength; `auto` shrinks without one.
     *
     * @param  'full'|'auto'|null  $width
     */
    public function inputWidth(?string $width): static
    {
        $this->inputWidth = $width;

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
                'labelSrOnly' => $this->labelSrOnly ?: null,
                'fieldset' => $this->fieldset ?: null,
                'showStatus' => $this->showStatus ? null : false,
                'labelHtml' => $this->labelHtml,
                'headingPrefix' => $this->headingPrefix,
                'headingSuffix' => $this->headingSuffix,
                'translatable' => $this->translatable ?: null,
                'translationDescription' => $this->translationDescription,
                'orientation' => $this->orientation,
                'inputWidth' => $this->inputWidth,
                'instructionsPosition' => $this->instructionsPosition !== 'before' ? $this->instructionsPosition : null,
                'instructionsHtml' => $this->noticeHtml($this->instructions),
                'tip' => $this->tip,
                'tipHtml' => $this->noticeHtml($this->tip),
                'warning' => $this->warning,
                'warningHtml' => $this->noticeHtml($this->warning),
                'layoutUid' => $this->layoutUid,
                'width' => $this->width,
                'status' => $this->showStatus ? $this->status : null,
                'statusLabel' => $this->showStatus && $this->status !== null ? $this->statusLabel : null,
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
