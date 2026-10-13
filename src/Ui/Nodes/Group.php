<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Cp\Components\FieldGroup;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Enums\FieldWidth;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\Nodes\Concerns\HasFieldPresentation;
use CraftCms\Cms\Ui\Nodes\Concerns\HasVisibility;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * A labelled container whose children lay out on a `<craft-field-group>` grid.
 *
 * Children resolve at the surrounding namespace, so each child Control keeps
 * the path it declares and the Group contributes nothing to the Ui's values.
 *
 * Two appearances:
 *
 * - **Section** (default) — a `<fieldset>` with the label as its `<legend>`, or
 *   a `<craft-disclosure>` when {@see self::collapsible()}. For a run of
 *   settings under a heading (“Advanced”, “Field Limit”).
 * - **Field** ({@see self::asField()}) — a `<craft-field>` in fieldset mode,
 *   for several inputs that make up *one* logical field (“Asset Location” over
 *   a source select and a subpath input). Presentation settings from
 *   HasFieldPresentation apply to this appearance, and the label reads as
 *   a field label rather than a heading.
 *
 * Field appearance renders `role="group"` + `aria-labelledby` rather than a
 * `label[for]`, since one label can't address several inputs — the ARIA17
 * equivalent of the `<fieldset>`/`<legend>` technique (H71). Both are
 * sufficient for WCAG 1.3.1 and 3.3.2. Note that a group name *supplements*
 * per-control labels rather than replacing them, so children still need their
 * own accessible names.
 *
 * @since 6.0.0
 */
class Group extends Container
{
    use HasFieldPresentation;
    use HasVisibility;

    private bool $collapsible = false;

    private bool $expanded = false;

    private bool $asField = false;

    private ?int $width = null;

    /** @var list<string>|null */
    private ?array $dependsOn = null;

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        $label = $node->props['label'] ?? null;

        if ($node->props['asField'] ?? false) {
            return self::fieldHtml($node, $payload, $renderer);
        }

        $children = FieldGroup::make()
            ->children([new HtmlString($renderer->renderNodes($node->children ?? [], $payload))])
            ->attributes(['slot' => ($node->props['collapsible'] ?? false) ? 'content' : null]);
        $attributes = [
            'data-ui-node' => $node->uid,
            ...self::visibilityAttributes($node->props),
        ];

        if (isset($node->props['width'])) {
            $attributes['class'] = trim(($attributes['class'] ?? '')." width-{$node->props['width']}");
        }

        if ($node->props['collapsible'] ?? false) {
            return Html::tag('craft-disclosure', $children->toHtml(), [
                'label' => $label,
                'opened' => $node->props['expanded'] ?? false,
                ...$attributes,
            ]);
        }

        return Html::tag('fieldset', ($label ? Html::tag('legend', Html::encode($label)) : '').$children->toHtml(), $attributes);
    }

    /** @param list<Node> $children */
    public static function make(string $uid, array $children = []): self
    {
        return new self($uid, $children);
    }

    /** Section appearance only; ignored when {@see self::asField()} is set. */
    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    /** Section appearance only; ignored unless the group is {@see self::collapsible()}. */
    public function expanded(bool $expanded = true): static
    {
        $this->expanded = $expanded;

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

    /** How wide the group itself should be within its own container. */
    public function width(FieldWidth|int|null $width): static
    {
        $this->width = $width instanceof FieldWidth ? $width->value : $width;

        return $this;
    }

    /**
     * Shows a loading state while the reactive control at this absolute path refreshes the Ui.
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
            ...($this->collapsible && ! $this->asField && $this->expanded ? ['expanded' => true] : []),
            ...($this->asField ? ['asField' => true] : []),
            ...($this->asField && $this->required ? ['required' => true] : []),
            ...Arr::whereNotNull([
                'instructions' => $this->instructions,
                ...$this->fieldPresentationProps(),
                'width' => $this->width,
                'dependsOn' => $this->dependsOn,
            ]),
            ...$this->visibilityProps(),
        ];
    }

    private static function fieldHtml(
        NodePayload $node,
        UiPayload $payload,
        UiHtmlRenderer $renderer,
    ): string {
        $props = $node->props;

        return self::fieldComponent($props)
            ->fieldset()
            ->input(
                FieldGroup::make()
                    ->children([
                        new HtmlString($renderer->renderNodes($node->children ?? [], $payload)),
                    ])
                    ->attributes(['class' => 'auto-widths']),
            )
            ->attributes([
                'class' => isset($props['width']) ? "width-{$props['width']}" : null,
                'data-ui-node' => $node->uid,
                'data-layout-element' => $props['layoutUid'] ?? null,
            ])
            ->attributes(self::visibilityAttributes($props))
            ->toHtml();
    }
}
