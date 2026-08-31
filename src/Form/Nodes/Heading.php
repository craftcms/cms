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
    private ?string $description = null;

    private int $level = 2;

    private int $width = 100;

    public function __construct(
        private readonly string $uid,
        private readonly string $content,
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        $heading = Html::tag("h{$node->props['level']}", Html::encode($node->props['content']), [
            'class' => ['my-0'],
        ]);
        $description = $node->props['description'] === null
            ? ''
            : Html::tag('p', Html::encode($node->props['description']), [
                'class' => ['my-0', 'text-sm', 'text-on-neutral-quiet'],
            ]);

        return Html::tag('div', $heading.$description, [
            'class' => ['grid', 'gap-1', "width-{$node->props['width']}"],
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

    public function description(?string $description): static
    {
        $this->description = $description;

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
            'description' => $this->description,
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
