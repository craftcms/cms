<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Markdown as MarkdownFacade;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\Twig\Contracts\SafeHtml;
use Illuminate\Contracts\Support\Htmlable;
use Stringable;

#[AllowedInSandbox]
class MarkdownData implements Htmlable, SafeHtml, Serializable, Stringable
{
    public function __construct(
        private readonly string $raw,
        private readonly string $flavor,
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
        return MarkdownFacade::parse(Elements::parseRefs($this->raw), $this->flavor);
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
