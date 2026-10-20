<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Cp\Components\FieldGroup;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\HtmlString;

/**
 * @since 6.0.0
 */
class Tab extends Container
{
    private ?string $icon = null;

    private bool $sidebar = false;

    /** @param list<Node> $children */
    private function __construct(
        string $uid,
        private readonly string $label,
        array $children = [],
    ) {
        parent::__construct($uid, $children);
    }

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        $children = FieldGroup::make()
            ->children([new HtmlString($renderer->renderNodes($node->children ?? [], $payload))])
            ->toHtml();

        return Html::tag('section', $children, [
            'id' => $renderer->tabId($node, $payload),
            'class' => $renderer->isFirstTab($node, $payload) ? null : 'hidden',
            'aria' => ['label' => $node->props['label']],
            'data' => [
                'id' => $renderer->tabId($node, $payload),
                'ui-tab' => $node->uid,
                'layout-tab' => $node->uid,
            ],
        ]);
    }

    /** @param list<Node> $children */
    public static function make(string $uid, string $label, array $children = []): self
    {
        return new self($uid, $label, $children);
    }

    /** An icon shown before the tab's label. */
    public function icon(?string $icon): static
    {
        $this->icon = $icon !== '' ? $icon : null;

        return $this;
    }

    /**
     * Whether the tab belongs in the edit form's sidebar. Renderers without a
     * sidebar show it alongside the other tabs.
     */
    public function sidebar(bool $sidebar = true): static
    {
        $this->sidebar = $sidebar;

        return $this;
    }

    public function component(): string
    {
        return 'craft:tab';
    }

    public function props(): array
    {
        return array_filter([
            'label' => $this->label,
            'icon' => $this->icon,
            'sidebar' => $this->sidebar ?: null,
        ], fn (string|bool|null $value): bool => $value !== null);
    }
}
