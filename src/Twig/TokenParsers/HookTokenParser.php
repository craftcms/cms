<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\HookNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class HookTokenParser extends AbstractTokenParser
{
    public function getTag(): string
    {
        return 'hook';
    }

    public function parse(Token $token): HookNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $nodes = ['hook' => $this->parser->parseExpression()];

        $stream->expect(Token::BLOCK_END_TYPE);

        return new HookNode($nodes, [], $lineno);
    }
}
