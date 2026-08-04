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

class Group implements Node
{
    private ?string $label = null;

    /** @param list<Node> $children */
    private function __construct(private readonly string $uid, private array $children = []) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $label = $node->props['label'] ?? null;
        $children = FieldGroup::make()
            ->children([new HtmlString($renderer->renderNodes($node->children ?? [], $payload))])
            ->toHtml();

        return Html::tag('fieldset', ($label ? Html::tag('legend', Html::encode($label)) : '').$children, [
            'data-form-node' => $node->uid,
        ]);
    }

    /** @param list<Node> $children */
    public static function make(string $uid, array $children = []): self
    {
        return new self($uid, $children);
    }

    public function add(Node ...$children): static
    {
        array_push($this->children, ...$children);

        return $this;
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function component(): string
    {
        return 'craft:group';
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
