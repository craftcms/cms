<?php

declare(strict_types=1);

namespace CraftCms\Cms\Form\Nodes;

use CraftCms\Cms\Form\Contracts\Control;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormPayload;
use CraftCms\Cms\Form\NodePayload;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

class TemplateContent implements Node
{
    private int $width = 100;

    private function __construct(
        private readonly string $uid,
        private readonly string $html,
    ) {}

    public static function renderHtml(NodePayload $node, FormPayload $payload, FormHtmlRenderer $renderer): string
    {
        return Html::tag('div', $node->props['html'], [
            'class' => ["width-{$node->props['width']}"],
            'data-form-node' => $node->uid,
            'inert' => true,
        ]);
    }

    public static function make(string $uid, string $html): self
    {
        $config = app(HtmlSanitizerManager::class)->defaultConfig()
            ->blockElement('form');

        foreach (['button', 'input', 'optgroup', 'option', 'select', 'textarea'] as $element) {
            $config = $config->dropElement($element);
        }

        return new self($uid, new HtmlSanitizer($config)->sanitize($html));
    }

    public function width(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function component(): string
    {
        return 'craft:template-content';
    }

    public function uid(): ?string
    {
        return $this->uid;
    }

    public function props(): array
    {
        return [
            'html' => $this->html,
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
