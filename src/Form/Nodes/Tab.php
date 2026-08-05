<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\FieldGroup;
use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;

class Tab implements Node
{
    /** @param list<Node> $children */
    private function __construct(
        private readonly string $uid,
        private readonly string $label,
        private array $children = [],
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $children = FieldGroup::make()
            ->children([new HtmlString($renderer->renderNodes($node->children ?? [], $payload))])
            ->toHtml();

        return Html::tag('section', $children, [
            'aria' => ['label' => $node->props['label']],
            'data-form-tab' => $node->uid,
        ]);
    }

    /** @param list<Node> $children */
    public static function make(string $uid, string $label, array $children = []): self
    {
        return new self($uid, $label, $children);
    }

    public function add(Node ...$children): static
    {
        array_push($this->children, ...$children);

        return $this;
    }

    public function component(): string
    {
        return 'craft:tab';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return ['label' => $this->label];
    }

    public function getControl(): ?Control
    {
        return null;
    }

    public function children(): array
    {
        return $this->children;
    }
}
