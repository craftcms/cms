<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use Closure;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * Base class for PHP components that render a Craft custom element.
 *
 * Subclasses (typically generated from the custom elements manifest) provide
 * the {@see self::tagName()} and {@see self::hostAttributes()}, plus typed
 * fluent setters. This base supplies attribute merging and slot handling.
 *
 * Slot content is supplied as a closure (or a renderable value) and resolved
 * lazily when {@see self::toHtml()} is called, so child components can be
 * configured up until the parent is rendered. A slot callback may return a
 * string, an {@see Htmlable}/{@see Stringable}, or another {@see ViewComponent}
 * (which is rendered into the slot, with its `slot` attribute set for you).
 */
abstract class ViewComponent implements Htmlable, Stringable
{
    /** Key under which the default (unnamed) slot is stored. */
    protected const DEFAULT_SLOT = '';

    /** @var array<string, mixed> Additional host HTML attributes. */
    protected array $attributes = [];

    /** @var array<string, mixed> Slot content keyed by slot name. */
    protected array $slots = [];

    final public function __construct() {}

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Merges additional HTML attributes (e.g. `class`) onto the host element.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = Arr::merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Assigns this component to a named slot of its parent.
     */
    public function slot(string $name): static
    {
        $this->attributes['slot'] = $name;

        return $this;
    }

    /**
     * The custom element tag name, e.g. `craft-nav-item`.
     */
    abstract protected function tagName(): string;

    /**
     * Host element attributes derived from the component's typed properties.
     *
     * @return array<string, mixed>
     */
    protected function hostAttributes(): array
    {
        return [];
    }

    public function toHtml(): string
    {
        return Html::tag(
            $this->tagName(),
            $this->renderSlots(),
            Arr::merge($this->attributes, $this->hostAttributes()),
        );
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * Resolves and concatenates all assigned slot content.
     */
    protected function renderSlots(): string
    {
        $html = '';

        foreach ($this->slots as $name => $content) {
            $html .= $this->renderSlot($name, $content);
        }

        return $html;
    }

    protected function renderSlot(string $name, mixed $content): string
    {
        $resolved = $content instanceof Closure ? $content() : $content;

        if ($resolved === null || $resolved === '') {
            return '';
        }

        // A nested component drops straight into the named slot.
        if ($name !== static::DEFAULT_SLOT && $resolved instanceof self) {
            return $resolved->slot($name)->toHtml();
        }

        $rendered = $resolved instanceof Htmlable
            ? $resolved->toHtml()
            : Html::encode((string) $resolved);

        if ($name === static::DEFAULT_SLOT) {
            return $rendered;
        }

        // Other renderable content still needs a slotted wrapper element.
        return Html::tag('span', $rendered, ['slot' => $name]);
    }
}
