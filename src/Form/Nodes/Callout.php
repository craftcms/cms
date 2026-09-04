<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\Callout as CalloutComponent;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Conditionable;

class Callout implements Node
{
    use Conditionable;

    private string $variant = 'info';

    private ?string $appearance = null;

    private ?string $icon = null;

    private bool $dismissible = false;

    private int $width = 100;

    public function __construct(
        private readonly string $uid,
        private readonly string $content,
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return CalloutComponent::make()
            ->variant($node->props['variant'])
            ->appearance($node->props['appearance'] ?? null)
            ->icon($node->props['icon'] ?? null)
            ->content(new HtmlString($node->props['html']))
            ->attributes([
                'class' => ["width-{$node->props['width']}"],
                'data-form-node' => $node->uid,
                'data-dismissible' => $node->props['dismissible'],
            ])
            ->toHtml();
    }

    public static function make(string $uid, string $content): self
    {
        return new self($uid, $content);
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function appearance(?string $appearance): static
    {
        $this->appearance = $appearance;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function dismissible(bool $dismissible = true): static
    {
        $this->dismissible = $dismissible;

        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function component(): string
    {
        return 'craft:callout';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'html' => Markdown::parse(Html::encode($this->content), 'pre-encoded'),
            'variant' => $this->variant,
            ...array_filter([
                'appearance' => $this->appearance,
                'icon' => $this->icon,
            ], fn (?string $value): bool => $value !== null),
            'dismissible' => $this->dismissible,
            'width' => $this->width,
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
