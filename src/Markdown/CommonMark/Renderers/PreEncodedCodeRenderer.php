<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Renderers;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Xml\XmlNodeRendererInterface;

class PreEncodedCodeRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $code = $this->code($node);

        return new HtmlElement('code', $code->data->get('attributes'), $code->getLiteral());
    }

    public function getXmlTagName(Node $node): string
    {
        return 'code';
    }

    public function getXmlAttributes(Node $node): array
    {
        return [];
    }

    private function code(Node $node): Code
    {
        Code::assertInstanceOf($node);

        return $node instanceof Code ? $node : throw new InvalidArgumentException;
    }
}
