<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\DumpNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class DumpTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): DumpNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [];

        if (! $stream->test(Token::BLOCK_END_TYPE)) {
            $nodes['var'] = $this->parser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new DumpNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'dump';
    }
}
