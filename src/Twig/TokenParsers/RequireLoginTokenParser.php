<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RequireLoginNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class RequireLoginTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RequireLoginNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireLoginNode([], [], $lineno);
    }

    public function getTag(): string
    {
        return 'requireLogin';
    }
}
