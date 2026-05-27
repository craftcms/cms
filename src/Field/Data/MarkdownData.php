<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Markdown as MarkdownFacade;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Twig\Contracts\SafeHtml;
use Illuminate\Contracts\Support\Htmlable;

#[AllowedInSandbox]
readonly class MarkdownData implements Htmlable, SafeHtml, Serializable
{
    public function __construct(
        private string $raw,
        private string $flavor,
        private bool $encode = false,
        private bool $inlineOnly = false,
    ) {}

    public function __toString(): string
    {
        return $this->getHtml();
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getMarkdown(): string
    {
        return $this->raw;
    }

    public function getFlavor(): string
    {
        return $this->flavor;
    }

    public function getHtml(): string
    {
        $markdown = Elements::parseRefs($this->raw);

        if ($this->encode) {
            $markdown = Html::encode($markdown);
        }

        if ($this->inlineOnly) {
            return MarkdownFacade::parseParagraph($markdown, $this->flavor);
        }

        return MarkdownFacade::parse($markdown, $this->flavor);
    }

    public function toHtml(): string
    {
        return $this->getHtml();
    }

    public function serialize(): string
    {
        return $this->raw;
    }
}
