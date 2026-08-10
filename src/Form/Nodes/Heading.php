<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;

class Heading implements Node
{
    private int $level = 2;

    private int $width = 100;

    public function __construct(
        private readonly string $uid,
        private readonly string $content,
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return Html::tag("h{$node->props['level']}", Html::encode($node->props['content']), [
            'class' => ["width-{$node->props['width']}"],
            'data-form-node' => $node->uid,
        ]);
    }

    public static function make(string $uid, string $content): self
    {
        return new self($uid, $content);
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function level(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function component(): string
    {
        return 'craft:heading';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'content' => $this->content,
            'level' => $this->level,
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
