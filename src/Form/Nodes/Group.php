<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Cp\Components\FieldGroup;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\HtmlString;

class Group extends Container
{
    private ?string $label = null;

    private bool $collapsible = false;

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $label = $node->props['label'] ?? null;
        $children = FieldGroup::make()
            ->children([new HtmlString($renderer->renderNodes($node->children ?? [], $payload))])
            ->attributes(['slot' => ($node->props['collapsible'] ?? false) ? 'content' : null]);

        if ($node->props['collapsible'] ?? false) {
            return Html::tag('craft-disclosure', $children->toHtml(), [
                'label' => $label,
                'data-form-node' => $node->uid,
            ]);
        }

        return Html::tag('fieldset', ($label ? Html::tag('legend', Html::encode($label)) : '').$children->toHtml(), [
            'data-form-node' => $node->uid,
        ]);
    }

    /** @param list<Node> $children */
    public static function make(string $uid, array $children = []): self
    {
        return new self($uid, $children);
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function component(): string
    {
        return 'craft:group';
    }

    public function props(): array
    {
        return [
            'label' => $this->label,
            ...($this->collapsible ? ['collapsible' => true] : []),
        ];
    }
}
