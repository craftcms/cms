<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Stringable;
use Twig\Markup;

/**
 * Base class for PHP components that render a Craft custom element, following
 * the shape explored in craftcms/cms#19032 so generated components (from the
 * custom elements manifest) and hand-written ones share one API.
 *
 * Subclasses provide {@see self::tagName()} and {@see self::hostAttributes()},
 * plus typed fluent setters. This base supplies attribute merging and slot
 * handling.
 *
 * A slot value may resolve to a string, an {@see Htmlable}/{@see Stringable},
 * or another {@see ViewComponent} (rendered into the slot, with its `slot`
 * attribute set for you). Plain strings are HTML-encoded; pass an `Htmlable`
 * (e.g. `HtmlString`) for trusted markup.
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
     * Applies a config array by calling the matching fluent setters —
     * kebab/snake keys are camelized, so Twig-style hashes work:
     *
     *     {{ ui('callout', {variant: 'warning', content: 'Careful!'}) }}
     *
     * @param  array<string, mixed>  $config
     */
    public function configure(array $config): static
    {
        foreach ($config as $key => $value) {
            $method = Str::camel((string) $key);

            // Only fluent setters are configurable — getters
            // (get*/is*) and the rendering API would otherwise swallow the
            // value silently, since PHP ignores extra arguments.
            $notASetter = in_array($method, ['configure', 'make', 'toHtml'], true)
                || preg_match('/^(get|is|render)[A-Z]/', $method) === 1;

            if ($notASetter || ! method_exists($this, $method)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown %s config key "%s".',
                    static::class,
                    $key,
                ));
            }

            $this->$method($value);
        }

        return $this;
    }

    /**
     * Merges additional HTML attributes (e.g. `class`) onto the host element.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function attributes(array $attributes): static
    {
        $this->attributes = Arr::merge(
            self::normalizeClasses($this->attributes),
            self::normalizeClasses($attributes),
        );

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
     * The custom element tag name, e.g. `craft-field`.
     */
    abstract protected function tagName(): string;

    /**
     * Host element attributes derived from the component's typed properties.
     * Values follow {@see Html::renderTagAttributes()} semantics: `true`
     * renders a bare attribute, `null`/`false` omit it.
     *
     * @return array<string, mixed>
     */
    protected function hostAttributes(): array
    {
        return [];
    }

    /**
     * The Blade view backing this component, or `null` to render markup
     * directly (the right default for single-element components and
     * manifest-generated classes). View-backed components receive
     * {@see self::viewData()}.
     */
    protected ?string $view = null;

    public function toHtml(): string
    {
        if ($this->view !== null) {
            return view($this->view, $this->viewData())->render();
        }

        return $this->renderMarkup();
    }

    /**
     * Direct markup rendering: the custom element tag wrapping the slot
     * content.
     */
    protected function renderMarkup(): string
    {
        return Html::tag(
            $this->tagName(),
            $this->renderSlots(),
            $this->renderedAttributes(),
        );
    }

    /**
     * Data passed to a view-backed component's Blade template. The base
     * payload keeps slot-attachment and attribute semantics in PHP so
     * templates stay layout-only; subclasses add their own pieces.
     *
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        return [
            'component' => $this,
            'tagName' => $this->tagName(),
            'attributes' => new HtmlString(Html::renderTagAttributes($this->renderedAttributes())),
            'slots' => new HtmlString($this->renderSlots()),
        ];
    }

    /** @return array<string, mixed> */
    protected function renderedAttributes(): array
    {
        return Arr::merge($this->attributes, $this->hostAttributes());
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    /**
     * Renders and concatenates all assigned slot content.
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
        if ($content === null || $content === '') {
            return '';
        }

        // The default slot may hold a list of children (e.g. a field group's
        // fields); render each in turn.
        if ($name === static::DEFAULT_SLOT && is_iterable($content)) {
            $html = '';

            foreach ($content as $item) {
                $html .= $this->renderSlot($name, $item);
            }

            return $html;
        }

        // A nested component drops straight into the named slot.
        if ($name !== static::DEFAULT_SLOT && $content instanceof self) {
            return $content->slot($name)->toHtml();
        }

        $rendered = $this->renderContent($content);

        if ($name === static::DEFAULT_SLOT) {
            return $rendered;
        }

        return $this->slotted($rendered, $name);
    }

    /**
     * Renders a content value to HTML: `Htmlable` passes through raw,
     * Twig `Markup` (e.g. `{% set %}` captures) is pre-escaped by Twig, and
     * anything else is treated as literal text and encoded.
     */
    protected function renderContent(mixed $resolved): string
    {
        return match (true) {
            $resolved instanceof Htmlable => $resolved->toHtml(),
            $resolved instanceof Markup => (string) $resolved,
            default => Html::encode((string) $resolved),
        };
    }

    /**
     * Assigns rendered HTML to a named slot: sets `slot` directly on a single
     * root element, or wraps the content in a `<span>`. Setting the attribute
     * on the root element matters for slots whose element the component
     * inspects (e.g. a field's `input` slot, which must not be a wrapper).
     */
    protected function slotted(string $html, string $slot): string
    {
        if (str_starts_with(ltrim($html), '<')) {
            try {
                return Html::modifyTagAttributes($html, ['slot' => $slot]);
            } catch (InvalidArgumentException) {
                // Fall through to wrapping.
            }
        }

        return Html::tag('span', $html, ['slot' => $slot]);
    }

    /**
     * Explodes `class` strings into arrays so merges union them.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function normalizeClasses(array $attributes): array
    {
        if (isset($attributes['class'])) {
            $attributes['class'] = Html::explodeClass($attributes['class']);
        }

        return $attributes;
    }
}
