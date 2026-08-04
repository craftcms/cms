<?php

declare(strict_types=1);

use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedCodeRenderer;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedFencedCodeRenderer;
use CraftCms\Cms\Markdown\CommonMark\Renderers\PreEncodedIndentedCodeRenderer;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;

beforeEach(function () {
    $this->childRenderer = new class implements ChildNodeRendererInterface
    {
        public function renderNodes(iterable $nodes): string
        {
            return '';
        }

        public function getBlockSeparator(): string
        {
            return "\n";
        }

        public function getInnerSeparator(): string
        {
            return "\n";
        }
    };
});

describe('PreEncoded renderers', function () {
    it('renders inline code without double-encoding', function () {
        $node = new Code('&lt;b class=&quot;x&quot;&gt;');
        $renderer = new PreEncodedCodeRenderer;

        expect((string) $renderer->render($node, $this->childRenderer))
            ->toBe('<code>&lt;b class=&quot;x&quot;&gt;</code>');
    });

    it('renders fenced code without double-encoding and preserves language classes', function () {
        $node = new FencedCode(3, '`', 0);
        $node->setInfo('html');
        $node->setLiteral('&lt;b class=&quot;x&quot;&gt;');

        $renderer = new PreEncodedFencedCodeRenderer;

        expect((string) $renderer->render($node, $this->childRenderer))
            ->toBe('<pre><code class="language-html">&lt;b class=&quot;x&quot;&gt;</code></pre>');
    });

    it('does not duplicate a language prefix for fenced code', function () {
        $node = new FencedCode(3, '`', 0);
        $node->setInfo('language-html');
        $node->setLiteral('&lt;b&gt;');

        $renderer = new PreEncodedFencedCodeRenderer;

        expect((string) $renderer->render($node, $this->childRenderer))
            ->toBe('<pre><code class="language-html">&lt;b&gt;</code></pre>');
    });

    it('renders indented code without double-encoding', function () {
        $node = new IndentedCode;
        $node->setLiteral('&lt;b class=&quot;x&quot;&gt;');

        $renderer = new PreEncodedIndentedCodeRenderer;

        expect((string) $renderer->render($node, $this->childRenderer))
            ->toBe('<pre><code>&lt;b class=&quot;x&quot;&gt;</code></pre>');
    });

    it('exposes xml metadata for fenced code info strings', function () {
        $node = new FencedCode(3, '`', 0);
        $node->setInfo('html linenums');
        $renderer = new PreEncodedFencedCodeRenderer;

        expect($renderer->getXmlTagName($node))->toBe('code_block')
            ->and($renderer->getXmlAttributes($node))->toBe(['info' => 'html linenums']);
    });

    it('returns empty xml attributes for indented code', function () {
        $renderer = new PreEncodedIndentedCodeRenderer;

        expect($renderer->getXmlTagName(new IndentedCode))->toBe('code_block')
            ->and($renderer->getXmlAttributes(new IndentedCode))->toBe([]);
    });

    it('rejects unsupported node types', function (object $renderer, Node $node) {
        $renderer->render($node, $this->childRenderer);
    })->with([
        [new PreEncodedCodeRenderer, new IndentedCode],
        [new PreEncodedFencedCodeRenderer, new Code('test')],
        [new PreEncodedIndentedCodeRenderer, new Code('test')],
    ])->throws(InvalidArgumentException::class);
});
