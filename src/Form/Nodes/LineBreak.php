<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Traits\Conditionable;

class LineBreak implements Node
{
    use Conditionable;

    public function __construct(private readonly string $uid) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return Html::tag('div', '', [
            'class' => 'line-break',
            'data-form-node' => $node->uid,
        ]);
    }

    public static function make(string $uid): self
    {
        return new self($uid);
    }

    public function component(): string
    {
        return 'craft:line-break';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [];
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
