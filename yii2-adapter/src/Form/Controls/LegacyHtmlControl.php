<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Form\Controls;

use CraftCms\Cms\Form\ControlPayload;
use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlFragment;
use CraftCms\Cms\View\HtmlStack;
use InvalidArgumentException;

class LegacyHtmlControl extends Control
{
    private ?HtmlFragment $fragment = null;

    private ?string $namespace = null;

    private bool $expandValues = false;

    public static function renderHtml(ControlPayload $control, mixed $value, array $attributes, FormHtmlRenderer $renderer): string
    {
        $fragment = $control->props['fragment'] ?? null;

        if (!is_array($fragment) || !isset($fragment['html'], $fragment['headHtml'], $fragment['bodyHtml'])) {
            throw new InvalidArgumentException('Legacy HTML Controls require a captured fragment.');
        }

        $htmlStack = app(HtmlStack::class);

        if ($fragment['headHtml'] !== '') {
            $htmlStack->html((string) $fragment['headHtml'], Position::Head);
        }

        if ($fragment['bodyHtml'] !== '') {
            $htmlStack->html((string) $fragment['bodyHtml'], Position::BodyEnd);
        }

        return (string) $fragment['html'];
    }

    public function component(): string
    {
        return 'craft-legacy:html';
    }

    public function fragment(HtmlFragment $fragment, ?string $namespace): static
    {
        $this->fragment = $fragment;
        $this->namespace = $namespace;

        return $this;
    }

    public function props(mixed $value = null): array
    {
        if ($this->fragment === null) {
            throw new InvalidArgumentException('Legacy HTML Controls must capture their fragment before resolution.');
        }

        return [
            'fragment' => $this->fragment->toArray(),
            'namespace' => $this->namespace,
            'expandValues' => $this->expandValues,
        ];
    }

    public function expandValues(): static
    {
        $this->expandValues = true;

        return $this;
    }
}
