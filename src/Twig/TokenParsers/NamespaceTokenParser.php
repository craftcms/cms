<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\NamespaceNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class NamespaceTokenParser extends AbstractTokenParser
{
    public function getTag(): string
    {
        return 'namespace';
    }

    public function parse(Token $token): NamespaceNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $attributes = [];
        $nodes = [
            'namespace' => $this->parser->parseExpression(),
        ];

        if ($stream->test('withClasses')) {
            $attributes['withClasses'] = true;
            $stream->next();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['body'] = $this->parser->subparse($this->decideNamespaceEnd(...), true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new NamespaceNode($nodes, $attributes, $lineno);
    }

    public function decideNamespaceEnd(Token $token): bool
    {
        return $token->test('endnamespace');
    }
}
