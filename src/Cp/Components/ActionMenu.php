<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Components;

use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Url;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Crypt;
use Stringable;

use function CraftCms\Cms\t;

/**
 * A `<craft-action-menu>`.
 *
 * Items are held in the **client item shape** — the same descriptors
 * `craft-action-menu`'s `actions` property and the Vue `ActionMenu.vue`
 * wrapper consume (see `action-menu.types.ts`): `type`, `label`, `icon`,
 * `iconColor`, `variant`, `disabled`, `href`, `action`, … That way a caller
 * can either render markup here or ship the same items to the client as JSON
 * and get the same menu. {@see self::menuItems()} converts Craft's older
 * menu-item config arrays (see {@see MenuHtml::disclosureMenu()}) into it.
 *
 * Item behavior is declarative: each item's `action` is a descriptor that
 * `runAction()` executes client-side (see `@src/actions`). {@see
 * self::menuItems()} accepts two spellings —
 *
 * - a **string** `action` — a Craft controller action path, converted to an
 *   `{type: 'http', method: 'POST', …}` descriptor alongside
 *   `params`/`confirm`/`redirect`, replacing the legacy
 *   `formsubmit`/`data-action` mechanism.
 * - an **array** `action` — a ready-made descriptor, passed through verbatim.
 *   This is how items express behavior that isn't a form post, e.g.
 *   `['type' => 'event', 'name' => 'craft:edit-field', 'detail' => [...]]`.
 *
 * `requireElevatedSession` has no client-side equivalent in
 * `craft-action-item`/`runAction()` — there's no re-auth prompt hook — so it's
 * intentionally dropped. An item that needs it is still rejected server-side;
 * it just won't get the elevated-session modal first.
 *
 * This component does **not** namespace the IDs it renders. Callers whose
 * items register JS against those IDs must wrap both item collection and
 * rendering in one
 * {@see InputNamespace::namespaceInputs()}
 * closure themselves, so the IDs aren't doubled up.
 */
class ActionMenu extends ViewComponent
{
    /** @var list<array<string, mixed>> */
    protected array $items = [];

    protected ?string $label = null;

    protected string $icon = 'ellipsis';

    protected string|Htmlable|Stringable|ViewComponent|null $invoker = null;

    protected bool $disabled = false;

    protected bool $searchable = false;

    protected ?string $for = null;

    protected ?string $placement = null;

    protected ?int $distance = null;

    protected bool $matchInvokerWidth = false;

    protected bool $withoutArrow = false;

    /**
     * The menu items, already in the client item shape.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function items(array $items): static
    {
        $this->items = array_values($items);

        return $this;
    }

    /**
     * The menu items, in Craft's menu-item config-array shape.
     *
     * @param  list<array<string, mixed>>  $items
     * @param  bool  $normalize  Whether to run the items through
     *                           {@see MenuHtml::disclosureMenuItems()}, which
     *                           normalizes types, moves destructive items
     *                           behind a trailing `hr`, and trims
     *                           leading/trailing/repeated `hr`s.
     */
    public function menuItems(array $items, bool $normalize = true): static
    {
        $menuHtml = app(MenuHtml::class);

        if ($normalize) {
            $items = $menuHtml->disclosureMenuItems($items);
        }

        return $this->items($this->toClientItems($items, $menuHtml));
    }

    /** @return list<array<string, mixed>> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** The default invoker's accessible label. */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** The default invoker's icon. */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Replaces the default ellipsis button that opens the menu. */
    public function invoker(string|Htmlable|Stringable|ViewComponent|null $invoker): static
    {
        $this->invoker = $invoker;

        return $this;
    }

    /** Prevents the menu from opening, and marks the invoker `aria-disabled`. */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /** Adds a search input that filters the items as the user types. */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /** The ID of an external element to anchor the overlay to. */
    public function for(?string $for): static
    {
        $this->for = $for;

        return $this;
    }

    /** The overlay's placement relative to its anchor, e.g. `bottom-start`. */
    public function placement(?string $placement): static
    {
        $this->placement = $placement;

        return $this;
    }

    /** The overlay's offset from its anchor, in pixels. */
    public function distance(?int $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    /** Sizes the overlay to at least the invoker's width. */
    public function matchInvokerWidth(bool $matchInvokerWidth = true): static
    {
        $this->matchInvokerWidth = $matchInvokerWidth;

        return $this;
    }

    /** Accepted for API compatibility; `craft-popover` never renders an arrow. */
    public function withoutArrow(bool $withoutArrow = true): static
    {
        $this->withoutArrow = $withoutArrow;

        return $this;
    }

    protected function hostAttributes(): array
    {
        return [
            // `label`/`icon` describe the invoker. They only drive the web
            // component's *generated* invoker (data-driven mode) — a slotted
            // one, which this component always renders, takes precedence — but
            // they're the element's documented API, so keep them in sync.
            'label' => $this->label,
            'icon' => $this->icon,
            'disabled' => $this->disabled,
            'searchable' => $this->searchable,
            'for' => $this->for,
            'placement' => $this->placement,
            'distance' => $this->distance,
            'match-invoker-width' => $this->matchInvokerWidth,
            'without-arrow' => $this->withoutArrow,
        ];
    }

    protected function tagName(): string
    {
        return 'craft-action-menu';
    }

    /**
     * The menu is always rendered, even with zero items, so JS (e.g.
     * `Craft.addActionsToChip()`) always has a `[slot="content"]` container to
     * inject into.
     */
    protected function renderSlots(): string
    {
        return $this->invokerHtml().$this->contentHtml();
    }

    protected function invokerHtml(): string
    {
        if ($this->invoker !== null) {
            return $this->renderSlot('invoker', $this->invoker);
        }

        return Html::tag('craft-button', Html::tag('craft-icon', '', [
            'name' => $this->icon,
        ]), [
            'type' => 'button',
            'slot' => 'invoker',
            'variant' => 'plain',
            'inherit' => true,
            'size' => 'small',
            // Bare `icon` attribute triggers the icon-only button styling
            // (the icon itself is slotted content, not the `icon` prop — it
            // needs its own `aria-label`-free presentation since the button
            // already carries one).
            'icon' => true,
            'aria' => [
                'label' => $this->label ?? t('Actions'),
            ],
        ]);
    }

    protected function contentHtml(): string
    {
        return Html::tag('div', implode('', array_map(
            $this->itemHtml(...),
            $this->items,
        )), [
            'slot' => 'content',
        ]);
    }

    /**
     * Maps Craft's menu-item config arrays onto the client item shape.
     *
     * Field mapping from the legacy `disclosureMenu()`/`menuitem.twig`
     * contract: `destructive` → `variant: 'danger'`, `color` → `iconColor`,
     * `url` → `href`, `action`/`params`/`confirm`/`redirect` → `action`.
     * `label`/`html`, `description` and `handle` are kept as-is and become
     * slotted content (HTML) or pass through to `craft-action-item` (client).
     * Groups are flattened — `craft-action-item` has no group concept.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function toClientItems(array $items, MenuHtml $menuHtml): array
    {
        $clientItems = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? MenuItemType::Button;
            if ($type instanceof MenuItemType) {
                $type = $type->value;
            }

            if ($type === MenuItemType::HR->value) {
                $clientItems[] = ['type' => MenuItemType::HR->value];

                continue;
            }

            if ($type === MenuItemType::Group->value) {
                $clientItems = [...$clientItems, ...$this->toClientItems(
                    $menuHtml->normalizeMenuItems($item['items'] ?? []),
                    $menuHtml,
                )];

                continue;
            }

            $color = $item['color'] ?? null;
            if ($color instanceof Color) {
                $color = $color->value;
            }

            $clientItems[] = Arr::whereNotNull([
                'type' => $type,
                'id' => $item['id'] ?? sprintf('menu-item-%s', mt_rand()),
                'label' => isset($item['label']) ? (string) $item['label'] : null,
                'html' => isset($item['html']) ? (string) $item['html'] : null,
                'description' => isset($item['description']) ? (string) $item['description'] : null,
                'handle' => isset($item['handle']) ? (string) $item['handle'] : null,
                'icon' => $this->resolveIcon($item['icon'] ?? null),
                'iconColor' => $color ?: null,
                'href' => $type === MenuItemType::Link->value ? Url::url((string) ($item['url'] ?? '')) : null,
                'disabled' => ($item['disabled'] ?? false) ?: null,
                'hidden' => ($item['hidden'] ?? false) ?: null,
                'variant' => ($item['destructive'] ?? false) ? 'danger' : null,
                'action' => $this->itemAction($item),
                'attributes' => $item['attributes'] ?? null,
            ]);
        }

        return $clientItems;
    }

    /**
     * `craft-action-item` resolves its `icon` client-side (defaulting to the
     * `solid` family) with no knowledge of `Icons::LEGACY_ICON_MAP` or which
     * names are custom icons — resolve here and, when the icon isn't in the
     * default `solid` family, prefix it the same way `Navigation.php` does for
     * `custom-icons/*` icons.
     */
    protected function resolveIcon(mixed $icon): ?string
    {
        if (! $icon) {
            return null;
        }

        $resolved = Icons::resolveIconData($icon);

        return $resolved['family'] !== 'solid'
            ? "{$resolved['family']}/{$resolved['name']}"
            : $resolved['name'];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function itemAction(array $item): ?array
    {
        $action = $item['action'] ?? null;

        if ($action === null || $action === false || $action === '') {
            return null;
        }

        // Already a client-side action descriptor — pass it through.
        if (is_array($action)) {
            return $action;
        }

        $body = (array) ($item['params'] ?? []);
        if ($item['redirect'] ?? false) {
            $body['redirect'] = Crypt::encrypt((string) $item['redirect']);
        }

        return array_filter([
            'type' => 'http',
            'method' => 'POST',
            'url' => Url::actionUrl((string) $action),
            'body' => $body ?: null,
            'confirm' => $item['confirm'] ?? null,
        ], fn ($value) => $value !== null);
    }

    /**
     * Renders one client item as either an `<hr>` separator or a
     * `<craft-action-item>`.
     *
     * @param  array<string, mixed>  $item
     */
    protected function itemHtml(array $item): string
    {
        if (($item['type'] ?? null) === MenuItemType::HR->value) {
            return Html::tag('hr', '', [
                'class' => 'action-menu__separator',
                // `craft-action-menu`'s `::slotted()` styles can't reach a
                // light-DOM `<hr>`, so style it inline (mirrors
                // `action-menu.ts`'s `_renderItem()`).
                'style' => [
                    'margin' => '0',
                    'border' => '0',
                    'border-block-start' => '1px solid var(--c-color-neutral-border-quiet)',
                ],
            ]);
        }

        $action = $item['action'] ?? null;

        $attributes = Arr::merge([
            'id' => $item['id'] ?? sprintf('menu-item-%s', mt_rand()),
            'icon' => $item['icon'] ?? false,
            'icon-color' => $item['iconColor'] ?? false,
            'href' => $item['href'] ?? false,
            'disabled' => $item['disabled'] ?? false,
            'hidden' => $item['hidden'] ?? false,
            'variant' => $item['variant'] ?? false,
            'action' => $action ? Json::encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : false,
        ], $item['attributes'] ?? []);

        return Html::tag('craft-action-item', $this->itemContentHtml($item), $attributes);
    }

    /**
     * Renders a menu item's slotted content: its label (or raw `html`), plus
     * an optional secondary line for `description`/`handle` — the same
     * `menu-item-description` markup `_includes/menuitem.twig` and
     * `_includes/forms/componentSelect.twig` use.
     *
     * @param  array<string, mixed>  $item
     */
    protected function itemContentHtml(array $item): string
    {
        $labelHtml = isset($item['label'])
            ? Html::encode($item['label'])
            : (string) ($item['html'] ?? '');

        if (isset($item['description'])) {
            $secondaryHtml = Html::tag('span', Html::encode($item['description']), [
                'class' => ['menu-item-description', 'mt-2xs', 'smalltext', 'light'],
            ]);
        } elseif (isset($item['handle'])) {
            $secondaryHtml = Html::tag('span', Html::encode($item['handle']), [
                'class' => ['menu-item-description', 'mt-2xs', 'smalltext', 'light', 'code'],
            ]);
        } else {
            $secondaryHtml = null;
        }

        if ($secondaryHtml === null) {
            return $labelHtml;
        }

        return Html::tag('span', $labelHtml.$secondaryHtml, [
            'class' => ['inline-flex', 'flex-col', 'items-start', 'gap-2xs'],
        ]);
    }
}
