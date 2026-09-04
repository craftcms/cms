<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\Field as FieldComponent;
use CraftCms\Cms\Cp\Components\FieldGroup;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Enums\FieldWidth;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Form\Nodes\Concerns\HasVisibility;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * A labelled container whose children lay out on a `<craft-field-group>` grid.
 *
 * Children resolve at the surrounding namespace, so each child Control keeps
 * the path it declares and the Group contributes nothing to the Form's values.
 *
 * Two appearances:
 *
 * - **Section** (default) — a `<fieldset>` with the label as its `<legend>`, or
 *   a `<craft-disclosure>` when {@see self::collapsible()}. For a run of
 *   settings under a heading (“Advanced”, “Field Limit”).
 * - **Field** ({@see self::asField()}) — a `<craft-field>` in fieldset mode,
 *   for several inputs that make up *one* logical field (“Asset Location” over
 *   a source select and a subpath input). Unlocks instructions, tip, warning
 *   and a width, and the label reads as a field label rather than a heading.
 *
 * Field appearance renders `role="group"` + `aria-labelledby` rather than a
 * `label[for]`, since one label can't address several inputs — the ARIA17
 * equivalent of the `<fieldset>`/`<legend>` technique (H71). Both are
 * sufficient for WCAG 1.3.1 and 3.3.2. Note that a group name *supplements*
 * per-control labels rather than replacing them, so children still need their
 * own accessible names.
 */
class Group extends Container
{
    use HasVisibility;

    private ?string $label = null;

    private bool $collapsible = false;

    private bool $asField = false;

    private ?string $instructions = null;

    private ?string $tip = null;

    private ?string $warning = null;

    private ?int $width = null;

    /** @var list<string>|null */
    private ?array $dependsOn = null;

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $label = $node->props['label'] ?? null;

        if ($node->props['asField'] ?? false) {
            return self::fieldHtml($node, $payload, $renderer, $label);
        }

        $children = FieldGroup::make()
            ->children([new HtmlString($renderer->renderNodes($node->children ?? [], $payload))])
            ->attributes(['slot' => ($node->props['collapsible'] ?? false) ? 'content' : null]);

        if ($node->props['collapsible'] ?? false) {
            return Html::tag('craft-disclosure', $children->toHtml(), [
                'label' => $label,
                'data-form-node' => $node->uid,
                ...self::visibilityAttributes($node->props),
            ]);
        }

        return Html::tag('fieldset', ($label ? Html::tag('legend', Html::encode($label)) : '').$children->toHtml(), [
            'data-form-node' => $node->uid,
            ...self::visibilityAttributes($node->props),
        ]);
    }

    /** @param list<Node> $children */
    public static function make(string $uid, array $children = []): self
    {
        return new self($uid, $children);
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** Section appearance only; ignored when {@see self::asField()} is set. */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /**
     * Renders the group as one field rather than a section — see the class
     * docblock. Takes precedence over {@see self::collapsible()}.
     */
    public function asField(bool $asField = true): static
    {
        $this->asField = $asField;

        return $this;
    }

    /** Field appearance only. */
    public function instructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    /** Field appearance only. */
    public function tip(?string $tip): static
    {
        $this->tip = $tip;

        return $this;
    }

    /** Field appearance only. */
    public function warning(?string $warning): static
    {
        $this->warning = $warning;

        return $this;
    }

    /** How wide the group itself should be within its own container. */
    public function width(FieldWidth|int|null $width): static
    {
        $this->width = $width instanceof FieldWidth ? $width->value : $width;

        return $this;
    }

    /**
     * Shows a loading state while the reactive control at this relative path refreshes the Form.
     *
     * @param  string|list<string>  $path
     */
    public function dependsOn(string|array $path): static
    {
        $segments = is_string($path) ? explode('.', $path) : array_values($path);

        if ($segments === [] || ! array_all($segments, fn (mixed $segment): bool => is_string($segment) && $segment !== '')) {
            throw new InvalidArgumentException('Group loading paths must contain non-empty string segments.');
        }

        $this->dependsOn = $segments;

        return $this;
    }

    public function component(): string
    {
        return 'craft:group';
    }

    public function props(): array
    {
        return [
            'label' => $this->label,
            ...($this->collapsible && ! $this->asField ? ['collapsible' => true] : []),
            ...($this->asField ? ['asField' => true] : []),
            ...Arr::whereNotNull([
                'instructions' => $this->instructions,
                'tip' => $this->tip,
                'tipHtml' => $this->noticeHtml($this->tip),
                'warning' => $this->warning,
                'warningHtml' => $this->noticeHtml($this->warning),
                'width' => $this->width,
                'dependsOn' => $this->dependsOn,
            ]),
            ...$this->visibilityProps(),
        ];
    }

    private static function fieldHtml(
        NodePayload $node,
        FormPayload $payload,
        FormHtmlRenderer $renderer,
        ?string $label,
    ): string {
        $props = $node->props;

        return FieldComponent::make()
            ->fieldset()
            ->label($label)
            ->instructions($props['instructions'] ?? null)
            ->tip($props['tip'] ?? null)
            ->warning($props['warning'] ?? null)
            ->input(FieldGroup::make()->children([
                new HtmlString($renderer->renderNodes($node->children ?? [], $payload)),
            ]))
            ->attributes([
                'class' => isset($props['width']) ? "width-{$props['width']}" : null,
                'data-form-node' => $node->uid,
            ])
            ->attributes(self::visibilityAttributes($props))
            ->toHtml();
    }

    private function noticeHtml(?string $notice): ?string
    {
        return $notice === null
            ? null
            : Html::decodeDoubles(Markdown::parseParagraph(Html::encodeInvalidTags($notice)));
    }
}
