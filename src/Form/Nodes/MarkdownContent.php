<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Facades\HtmlSanitizers;
use CraftCms\Cms\Support\Facades\Markdown;
use CraftCms\Cms\Support\Html;
use Illuminate\Support\Traits\Conditionable;

class MarkdownContent implements Node
{
    use Conditionable;

    private bool $displayInPane = true;

    private int $width = 100;

    private function __construct(
        private readonly string $uid,
        private readonly string $html,
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return Html::tag('div', $node->props['html'], [
            'class' => array_filter([
                'markdown',
                $node->props['displayInPane'] ? 'pane' : null,
                "width-{$node->props['width']}",
            ]),
            'data-form-node' => $node->uid,
        ]);
    }

    public static function make(string $uid, string $content): self
    {
        return new self(
            $uid,
            HtmlSanitizers::sanitize(Markdown::parse($content)),
        );
    }

    public function displayInPane(bool $displayInPane = true): static
    {
        $this->displayInPane = $displayInPane;

        return $this;
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function component(): string
    {
        return 'craft:markdown-content';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'html' => $this->html,
            'displayInPane' => $this->displayInPane,
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
