<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\HeaderNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class HeaderTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): HeaderNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $nodes = [
            'header' => $this->parser->parseExpression(),
        ];
        $stream->expect(Token::BLOCK_END_TYPE);

        return new HeaderNode($nodes, [], $lineno);
    }

    public function getTag(): string
    {
        return 'header';
    }
}
