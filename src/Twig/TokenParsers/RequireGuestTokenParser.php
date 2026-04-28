<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\TokenParsers;

use CraftCms\Cms\Twig\Nodes\RequireGuestNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class RequireGuestTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): RequireGuestNode
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $stream->expect(Token::BLOCK_END_TYPE);

        return new RequireGuestNode([], [], $lineno);
    }

    public function getTag(): string
    {
        return 'requireGuest';
    }
}
