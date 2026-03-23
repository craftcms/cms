<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown\CommonMark\Renderers;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Xml\XmlNodeRendererInterface;

class PreEncodedFencedCodeRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        $fencedCode = $this->fencedCode($node);

        $attributes = $fencedCode->data->getData('attributes');
        $infoWords = $fencedCode->getInfoWords();

        if ($infoWords !== [] && $infoWords[0] !== '') {
            $class = str_starts_with($infoWords[0], 'language-') ? $infoWords[0] : "language-{$infoWords[0]}";
            $attributes->append('class', $class);
        }

        return new HtmlElement(
            'pre',
            [],
            new HtmlElement('code', $attributes->export(), $fencedCode->getLiteral()),
        );
    }

    public function getXmlTagName(Node $node): string
    {
        return 'code_block';
    }

    public function getXmlAttributes(Node $node): array
    {
        $fencedCode = $this->fencedCode($node);

        if (($info = $fencedCode->getInfo()) === null || $info === '') {
            return [];
        }

        return ['info' => $info];
    }

    private function fencedCode(Node $node): FencedCode
    {
        FencedCode::assertInstanceOf($node);

        return $node instanceof FencedCode ? $node : throw new InvalidArgumentException;
    }
}
