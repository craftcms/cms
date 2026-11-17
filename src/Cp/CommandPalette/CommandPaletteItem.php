<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\CommandPalette;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Search;
use CraftCms\Cms\Support\Str;

/**
 * One thing the command palette can list: a page to go to, an action to run,
 * or a search result.
 *
 * Pages, actions and search results are all the same shape, so a search
 * provider can return any mix of them and the palette draws them alike. What
 * happens when one is chosen depends on what it has, in this order:
 *
 * 1. {@see $view} – the palette swaps its search view for the given view.
 * 2. {@see $action} – the palette runs the declarative action (the same
 *    `BaseAction` shape `craft-action-item` runs), confirming it first if the
 *    action has a `confirm` message.
 * 3. {@see $href} – the palette goes there.
 *
 * ```php
 * new CommandPaletteItem()
 *     ->id('my-plugin-clear-caches')
 *     ->label(t('Clear My Plugin caches', category: 'my-plugin'))
 *     ->icon('broom')
 *     ->action([
 *         'type' => 'http',
 *         'url' => cp_url('my-plugin/clear-caches'),
 *     ]);
 * ```
 *
 * @phpstan-consistent-constructor
 *
 * @since 6.0.0
 */
class CommandPaletteItem extends Component
{
    /**
     * A stable identifier, unique among the items the palette is showing.
     * The palette remembers recently visited pages by it.
     */
    public ?string $id = null;

    public string $label = '';

    /**
     * The label as HTML, for inline formatting such as code. It's output as
     * is, so it must already be safe. {@see $label} is still what's searched.
     */
    public ?string $labelHtml = null;

    /** Secondary text shown after the label, such as where a page lives. */
    public ?string $description = null;

    /**
     * Supporting text shown below the label, such as a language's name in
     * that language. Searched along with the label.
     */
    public ?string $hint = null;

    /** The language {@see $hint} is in, when it differs from the control panel's. */
    public ?string $hintLang = null;

    /**
     * The hint as HTML, for inline formatting such as code. It's output as is,
     * so it must already be safe. {@see $hint} is still what's searched.
     */
    public ?string $hintHtml = null;

    public ?string $icon = null;

    /** A palette color for the icon, such as an entry type's — `red`, `teal`, and so on. */
    public ?string $iconColor = null;

    /** A rendered SVG to use in place of a named icon. Takes precedence over {@see $icon}. */
    public ?string $iconSvg = null;

    /** Thumbnail HTML to use in place of an icon, such as an asset's preview. */
    public ?string $thumbHtml = null;

    /** Where choosing the item goes, when it has no {@see $action} or {@see $view}. */
    public ?string $href = null;

    /** Leaves the control panel rather than making a client-side visit. */
    public bool $external = false;

    /**
     * A declarative action to run when the item is chosen.
     *
     * Any `confirm` message is shown inside the palette before the action
     * runs. An `http` action's response can carry `messages` to show and a
     * `redirect` to follow, as {@see RespondsWithFlash::asSuccess()}
     * provides.
     *
     * @var array<string, mixed>|null
     */
    public ?array $action = null;

    /**
     * A view to show in place of the search results when the item is chosen,
     * as a `type` plus whatever that view type takes.
     *
     * The palette knows the `ui` type – a server-built form, fetched from
     * `modalUrl` and submitted to `actionUrl` – and plugins can register more
     * with `Craft.commandPalette.registerView()`.
     *
     * @var array<string, mixed>|null
     */
    public ?array $view = null;

    /** Reloads the page after the item's action succeeds, for changes the page can't pick up otherwise. */
    public bool $reload = false;

    /**
     * A page the item's action does away with, such as the edit page of
     * something it deletes. If that's the page being viewed when the action
     * succeeds, the palette goes to {@see $removedPageRedirect} instead.
     */
    public ?string $removedPage = null;

    /** Where to go after the item's action removes the page being viewed. */
    public ?string $removedPageRedirect = null;

    /**
     * Secondary actions, offered from a menu beside the item.
     *
     * @var list<ActionItem>
     */
    public array $actions = [];

    /**
     * An element's own actions, offered from the same menu as {@see $actions},
     * as {@see ElementInterface::actionMenuDescriptors()}
     * describes them. The palette runs them the way element chips do.
     *
     * @var list<array<string, mixed>>
     */
    public array $elementActions = [];

    /** Hidden search terms. */
    public ?string $keywords = null;

    /**
     * Leaves the item out of the palette's initial list of actions, for
     * actions that come in large sets – one per locale, say – and only make
     * sense once someone searches for them.
     */
    public bool $searchOnly = false;

    /** `danger` for an item that destroys or disables something. */
    public ?string $variant = null;

    /**
     * Validation errors don't mean anything to the palette, so they're left
     * out of what it's sent.
     *
     * @return array<string, string|callable>
     */
    #[\Override]
    public function fields(): array
    {
        return Arr::except(parent::fields(), ['errors']);
    }

    /**
     * Unset properties are left out, rather than sent as nulls: the palette
     * receives hundreds of these at once, and most leave most properties
     * unset.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(array $fields = [], array $expand = [], bool $recursive = true): array
    {
        return array_filter(
            parent::toArray($fields, $expand, $recursive),
            fn (mixed $value): bool => $value !== null,
        );
    }

    public function id(?string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** @see $labelHtml */
    public function labelHtml(?string $html): static
    {
        $this->labelHtml = $html;

        return $this;
    }

    /** @see $hintHtml */
    public function hintHtml(?string $html): static
    {
        $this->hintHtml = $html;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function hint(?string $hint, ?string $lang = null): static
    {
        $this->hint = $hint;
        $this->hintLang = $lang;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * A system icon's name to show for something that chooses its own icon,
     * like a field or an entry type — or null when that's an SVG file or
     * markup, which an item's `icon` can't take.
     */
    public static function systemIcon(?string $icon): ?string
    {
        $icon = $icon !== null ? Icons::resolveIconName($icon) : null;

        return $icon !== null && preg_match('/^[\w\-\/]+$/', $icon) ? $icon : null;
    }

    public function iconColor(?string $iconColor): static
    {
        $this->iconColor = $iconColor;

        return $this;
    }

    public function iconSvg(?string $iconSvg): static
    {
        $this->iconSvg = $iconSvg;

        return $this;
    }

    public function thumbHtml(?string $thumbHtml): static
    {
        $this->thumbHtml = $thumbHtml;

        return $this;
    }

    public function href(?string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function external(bool $external = true): static
    {
        $this->external = $external;

        return $this;
    }

    /** @param array<string, mixed>|null $action */
    public function action(?array $action): static
    {
        $this->action = $action;

        return $this;
    }

    /** @param array<string, mixed>|null $view */
    public function view(?array $view): static
    {
        $this->view = $view;

        return $this;
    }

    public function reload(bool $reload = true): static
    {
        $this->reload = $reload;

        return $this;
    }

    /**
     * Sets the page the item's action does away with, and where to go from it
     * once the action succeeds.
     */
    public function removesPage(string $url, string $redirect): static
    {
        $this->removedPage = $url;
        $this->removedPageRedirect = $redirect;

        return $this;
    }

    /** @param list<ActionItem> $actions */
    public function actions(array $actions): static
    {
        $this->actions = array_values($actions);

        return $this;
    }

    /** @param list<array<string, mixed>> $elementActions */
    public function elementActions(array $elementActions): static
    {
        $this->elementActions = array_values($elementActions);

        return $this;
    }

    public function keywords(?string $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function searchOnly(bool $searchOnly = true): static
    {
        $this->searchOnly = $searchOnly;

        return $this;
    }

    public function variant(?string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Whether every word of the query appears in the item's label,
     * description, hint or keywords, ignoring case and accents.
     */
    public function matches(string $query): bool
    {
        $words = array_filter(explode(' ', Search::normalizeKeywords($query)));

        if ($words === []) {
            return true;
        }

        $haystack = Search::normalizeKeywords(implode(' ', array_filter([
            $this->label,
            $this->description,
            $this->hint,
            $this->keywords,
        ])));

        return array_all($words, fn ($word) => Str::contains($haystack, $word));
    }
}
