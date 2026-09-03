<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use CraftCms\Cms\Component\Component;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * @phpstan-consistent-constructor
 *
 * One labelled thing the control panel can offer: a navigation entry, a
 * breadcrumb, an item in a menu, a button.
 *
 * These were three separate shapes — a `NavItem` class, hand-written crumb
 * arrays, and hand-written action-menu arrays — which is how they came to
 * spell the same field three ways and support different subsets of the same
 * ideas. A source that appears in the sources sidebar, in the breadcrumb
 * switcher and in a collapsed menu is one thing rendered three ways, so it is
 * described once here and projected by whatever draws it.
 *
 * Everything is optional: a crumb often has no link, a group heading is not a
 * destination, and a separator has neither. Renderers read the parts they
 * understand and ignore the rest.
 *
 * @see \CraftCms\Cms\Cp\Data\NavItem, the name the navigation and plugins use.
 */
class ActionItem extends Component
{
    public ?string $label = null;

    /** Server-rendered content in place of a label, e.g. an element chip. */
    public ?string $html = null;

    public ?string $ariaLabel = null;

    /**
     * Where it goes, spelled the way HTML spells it so a renderer can hand it
     * straight to an anchor.
     *
     * Null for the things that aren't destinations — a group heading, the
     * crumb naming the page you're already on, a button that runs an action.
     */
    public ?string $href = null;

    /** Leaves the app rather than making a client-side visit. */
    public bool $external = false;

    public ?string $icon = null;

    public ?string $fontIcon = null;

    public ?string $iconColor = null;

    public ?string $id = null;

    public ?string $variant = null;

    public int $badgeCount = 0;

    /** Whether this is the one currently in effect. */
    public bool $selected = false;

    public bool $disabled = false;

    /**
     * Whether this names the page you're on.
     *
     * The Vue breadcrumbs derive this from position — the last crumb is the
     * current one — but the legacy template reads it, so it's carried.
     */
    public bool $current = false;

    /**
     * Renders as a heading over its children rather than as a destination.
     */
    public bool $group = false;

    /**
     * Its descendants — the tree below it.
     *
     * `false` rather than `[]` for a leaf, which is what lets a renderer tell
     * "no children" from "children not resolved yet".
     *
     * @var static[]|false
     */
    #[LiteralTypeScriptType('CraftCms.Cms.Cp.Data.ActionItem[] | false')]
    public array|false $subnav = false;

    /**
     * Its peers, for the places that offer a choice rather than a hierarchy:
     * the switcher hanging off a breadcrumb, or a group's members.
     *
     * Distinct from {@see $subnav} on purpose — those are the things beneath
     * this one, these are the things beside it.
     *
     * @var list<array<string, mixed>|self>
     */
    public array $items = [];

    /** Hidden search terms, for a menu that can be filtered. */
    public ?string $keywords = null;

    /**
     * A keyboard shortcut, as a key or `{key, alt, shift}`.
     *
     * @var string|array<string, mixed>|null
     */
    public string|array|null $shortcut = null;

    /**
     * A declarative action to run instead of following {@see $url}.
     *
     * @var array<string, mixed>|null
     */
    public ?array $action = null;

    /** @var array<string, mixed>|null */
    public ?array $feedback = null;

    /** @var array<string, mixed> */
    public array $linkAttributes = [];

    /** @param array<string, mixed>|object $config */
    public function __construct(array|object $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        // `url` is what the navigation and the legacy templates have always
        // called it, so configuring one from an older array still works.
        if (isset($config['url']) && ! isset($config['href'])) {
            $config['href'] = $config['url'];
        }

        unset($config['url']);

        if (isset($config['subnav']) && is_array($config['subnav'])) {
            $config['subnav'] = array_map(
                fn ($item) => $item instanceof self ? $item : new static($item),
                $config['subnav'],
            );
        }

        parent::__construct($config);
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function html(?string $html): static
    {
        $this->html = $html;

        return $this;
    }

    public function ariaLabel(?string $ariaLabel): static
    {
        $this->ariaLabel = $ariaLabel;

        return $this;
    }

    public function href(?string $href): static
    {
        $this->href = $href;

        return $this;
    }

    /**
     * {@see $href} under the name it used to have.
     *
     * The navigation, the legacy templates and plugins all said `url`, and a
     * [[Component]] throws on an unknown property rather than returning null
     * — so `item.url` would blow up rather than quietly miss. Reading and
     * writing both keep working; `href` is the spelling to write now, since
     * it's the one HTML uses.
     */
    public function getUrl(): ?string
    {
        return $this->href;
    }

    public function url(?string $url): static
    {
        return $this->href($url);
    }

    public function external(bool $external): static
    {
        $this->external = $external;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function fontIcon(?string $fontIcon): static
    {
        $this->fontIcon = $fontIcon;

        return $this;
    }

    public function iconColor(?string $iconColor): static
    {
        $this->iconColor = $iconColor;

        return $this;
    }

    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function variant(?string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function badgeCount(int $badgeCount): static
    {
        $this->badgeCount = $badgeCount;

        return $this;
    }

    public function selected(bool $selected): static
    {
        $this->selected = $selected;

        return $this;
    }

    public function disabled(bool $disabled): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function current(bool $current): static
    {
        $this->current = $current;

        return $this;
    }

    /**
     * Answers the legacy crumbs template's `crumb.menu`.
     *
     * It has to be answered somehow: a [[Component]] throws on an unknown
     * property rather than returning null, and Twig's `??` doesn't save it,
     * so the screens still drawn by Twig would 500 on the first crumb.
     *
     * Null on purpose. Those screens have never shown crumb switchers, and
     * the template's menu path merges the crumb as an array, which a DTO is
     * not — surfacing {@see $items} here would mean reworking that template
     * rather than answering a question it asks.
     *
     * @return array<string, mixed>|null
     */
    public function getMenu(): ?array
    {
        return null;
    }

    public function group(bool $group): static
    {
        $this->group = $group;

        return $this;
    }

    /** @param static[]|false $subnav */
    public function subnav(array|false $subnav): static
    {
        $this->subnav = $subnav;

        return $this;
    }

    /** @param static $item */
    public function add(self $item): static
    {
        if ($this->subnav === false) {
            $this->subnav = [];
        }

        $this->subnav[] = $item;

        return $this;
    }

    /** @param list<array<string, mixed>|self> $items */
    public function items(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function keywords(?string $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    /** @param string|array<string, mixed>|null $shortcut */
    public function shortcut(string|array|null $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    /** @param array<string, mixed>|null $action */
    public function action(?array $action): static
    {
        $this->action = $action;

        return $this;
    }

    /** @param array<string, mixed>|null $feedback */
    public function feedback(?array $feedback): static
    {
        $this->feedback = $feedback;

        return $this;
    }

    /** @param array<string, mixed> $linkAttributes */
    public function linkAttributes(array $linkAttributes): static
    {
        $this->linkAttributes = $linkAttributes;

        return $this;
    }
}
