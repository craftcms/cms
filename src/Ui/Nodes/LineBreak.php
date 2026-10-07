<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;

/**
 * @since 6.0.0
 */
class LineBreak implements Node
{
    use Conditionable;

    public function __construct(private readonly string $uid) {}

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return Html::tag('div', '', [
            'class' => 'line-break',
            'data-ui-node' => $node->uid,
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
