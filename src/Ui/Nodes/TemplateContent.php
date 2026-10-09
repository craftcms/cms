<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ui\Nodes;

use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\NodePayload;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiPayload;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

/**
 * @since 6.0.0
 */
class TemplateContent implements Node
{
    use Conditionable;

    private int $width = 100;

    private bool $trusted = false;

    private function __construct(
        private readonly string $uid,
        private readonly string $html,
    ) {}

    public static function renderHtml(NodePayload $node, UiPayload $payload, UiHtmlRenderer $renderer): string
    {
        return Html::tag('div', $node->props['html'], [
            'class' => ["width-{$node->props['width']}"],
            'data-ui-node' => $node->uid,
            'inert' => $node->props['inert'],
        ]);
    }

    public static function make(string $uid, string $html): self
    {
        return new self($uid, $html);
    }

    /**
     * Trusted HTML is rendered without sanitization or interaction restrictions.
     * Only opt in for developer-controlled markup with untrusted values escaped.
     */
    public function trusted(bool $trusted = true): static
    {
        $this->trusted = $trusted;

        return $this;
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
        $html = $this->html;

        if (! $this->trusted) {
            $config = app(HtmlSanitizerManager::class)->defaultConfig()
                ->blockElement('form');

            foreach (['button', 'input', 'optgroup', 'option', 'select', 'textarea'] as $element) {
                $config = $config->dropElement($element);
            }

            $html = new HtmlSanitizer($config)->sanitize($html);
        }

        return [
            'html' => $html,
            'width' => $this->width,
            'inert' => ! $this->trusted,
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
