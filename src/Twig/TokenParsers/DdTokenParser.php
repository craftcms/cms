<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\DdNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class DdTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): DdNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        if (! $stream->test(Token::BLOCK_END_TYPE)) {
            $nodes['var'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new DdNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'dd';
    }
}
