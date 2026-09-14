<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\ActionMenu as ActionMenuComponent;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use Illuminate\Support\Traits\Conditionable;

use function CraftCms\Cms\t;

/**
 * A `<craft-action-menu>` — typically in a {@see Field::actions()} slot, as a
 * field's "⋮" menu.
 *
 * Items arrive in Craft's menu-item config-array shape and are converted up
 * front to the client item shape, so the same `props` drive both the HTML
 * render path and the Vue one. Behavior travels with each item as a
 * declarative `action` descriptor rather than registered JS — see
 * {@see ActionMenuComponent}.
 */
class ActionMenu implements Node
{
    use Conditionable;

    private ?string $label = null;

    private string $icon = 'ellipsis';

    /** @param  list<array<string, mixed>>  $items  Client-shape items. */
    private function __construct(
        private readonly string $uid,
        private readonly array $items,
    ) {}

    /**
     * @param  string  $uid  Stable and unique within the form — control-less
     *                       nodes are keyed by it.
     * @param  list<array<string, mixed>>  $menuItems  Craft menu-item configs.
     */
    public static function make(string $uid, array $menuItems): self
    {
        $items = ActionMenuComponent::make()->menuItems($menuItems)->getItems();

        // Item IDs are generated with `mt_rand()` and only matter to the HTML
        // path (where `ElementHtml` keys chip behavior off their prefixes).
        // Leaving them in `props()` would make the payload differ on every
        // render, which reads as a change to a refreshable form.
        foreach ($items as &$item) {
            unset($item['id']);
        }
        unset($item);

        return new self($uid, $items);
    }

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return ActionMenuComponent::make()
            ->items($node->props['items'])
            ->icon((string) $node->props['icon'])
            ->label($node->props['label'])
            ->attributes(['data-form-node' => $node->uid])
            ->toHtml();
    }

    /** The invoker's accessible label. Defaults to “Actions”. */
    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /** The invoker's icon. */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /** Whether the menu ended up with anything to show. */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function component(): string
    {
        return 'craft:action-menu';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'items' => $this->items,
            'icon' => $this->icon,
            'label' => $this->label ?? t('Actions'),
        ];
    }

    public function getControl(): ?Control
    {
        return null;
    }

    public function children(): array
    {
        return [];
    }
}
