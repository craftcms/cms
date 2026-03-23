<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Renderers;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Xml\XmlNodeRendererInterface;

class PreEncodedIndentedCodeRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $indentedCode = $this->indentedCode($node);

        return new HtmlElement(
            'pre',
            [],
            new HtmlElement('code', $indentedCode->data->get('attributes'), $indentedCode->getLiteral()),
        );
    }

    public function getXmlTagName(Node $node): string
    {
        return 'code_block';
    }

    public function getXmlAttributes(Node $node): array
    {
        return [];
    }

    private function indentedCode(Node $node): IndentedCode
    {
        IndentedCode::assertInstanceOf($node);

        return $node instanceof IndentedCode ? $node : throw new InvalidArgumentException;
    }
}
